<?php

namespace App\Livewire\AI;

use App\Models\AIConversation;
use App\Models\AIMessage;
use App\Services\AI\Contracts\AIAssistantInterface;
use Livewire\Component;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ChatWidget extends Component
{
    // Basic properties
    public ?AIConversation $conversation = null;
    public bool $isTyping = false;
    public string $newMessageText = '';
    public $isInputDisabled = false;
    public ?string $currentTypingResponse = null;
    public ?int $typingMessageId = null;
    
    // Define the protected $queryString array to prevent query string parameters
    protected $queryString = [];
    
    // Define Livewire listeners for events
    protected $listeners = [
        'refreshComponent' => '$refresh',
        'processAIResponseAsync' => 'processAIResponse'
    ];
    
    // Page load
    public function mount()
    {
        $this->getOrCreateConversation();
    }
    
    // Get or create active conversation
    protected function getOrCreateConversation()
    {
        $this->conversation = auth()->user()->aiConversations()
            ->where('is_active', true)
            ->first();

        if (!$this->conversation) {
            $this->conversation = auth()->user()->aiConversations()->create([
                'title' => 'Yeni Sohbet',
                'is_active' => true
            ]);
        }
    }

    // Send message
    public function sendMessage()
    {
        // Empty message check
        if (empty(trim($this->newMessageText))) {
            return;
        }

        try {
            // Store message text before clearing
            $messageText = $this->newMessageText;
            
            // Clear input immediately
            $this->newMessageText = '';
            
            // Disable input while waiting for response
            $this->isInputDisabled = true;
            
            // Save user message
            $this->conversation->messages()->create([
                'role' => 'user',
                'content' => $messageText
            ]);
            
            // Set AI typing state
            $this->isTyping = true;
            
            // HEMEN bileşeni yenileyin, kullanıcı mesajı görünsün ve input temizlensin/disable olsun
            $this->dispatch('chatUpdated');
            
            // AI yanıtını işlemek için geciktirilmiş çağrı - daha iyi UI deneyimi için
            // Bu JavaScript fonksiyonu ile ön tarafa mesajın gösterilmesi ve 
            // ardından API çağrısının yapılması işlemleri ayrılıyor
            $this->dispatch('processAIQuery', messageText: $messageText);
            
        } catch (\Exception $e) {
            Log::error('Message Sending Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Re-enable input
            $this->isInputDisabled = false;
            $this->isTyping = false;
            
            $this->dispatch('$refresh');
        }
    }
    
    // Process AI response
    public function processAIResponse($messageText)
    {
        try {
            // DB şeması yükleme ve analiz servisleri
            $databaseSchemaService = app(\App\Services\AI\DatabaseSchemaService::class);
            $sqlQueryService = app(\App\Services\AI\SqlQueryService::class);
            $aiAssistant = app(AIAssistantInterface::class);
            
            // 1. AŞAMA: Kullanıcının sorusunu analiz et ve SQL sorgusu oluştur (gerekirse)
            $schemaData = $databaseSchemaService->getSchema();
            $analysisResult = $aiAssistant->generateSqlQuery(
                auth()->user(),
                $messageText,
                $schemaData
            );
            
            Log::info('AI question analysis', [
                'requires_sql' => $analysisResult['requires_sql'],
                'query' => $analysisResult['query'] ?? 'N/A',
                'explanation' => $analysisResult['explanation']
            ]);
            
            // 2. AŞAMA: SQL sorgusu gerekiyorsa, çalıştır ve sonuçları AI'ya gönder
            if ($analysisResult['requires_sql'] && !empty($analysisResult['query'])) {
                try {
                    // SQL sorgusu çalıştır
                    $sqlResults = $sqlQueryService->executeQuery($analysisResult['query']);
                    
                    // SQL sonuçlarıyla AI'dan yanıt al
                    $response = $aiAssistant->queryWithSqlResults(
                        auth()->user(),
                        $messageText,
                        $analysisResult['query'],
                        $sqlResults,
                        (string) $this->conversation->id
                    );
                } catch (\App\Services\AI\Exceptions\UnsafeSqlException $e) {
                    // Güvensiz SQL sorgusu
                    Log::warning('Unsafe SQL query attempted', [
                        'message' => $e->getMessage(),
                        'query' => $analysisResult['query'] ?? 'N/A'
                    ]);
                    
                    $response = "Üzgünüm, bu tür bir sorgu güvenlik sebebiyle çalıştırılamıyor. " .
                                "Lütfen sadece veri okuma isteklerinde bulunun. Detay: " . $e->getMessage();
                } catch (\Illuminate\Database\QueryException $e) {
                    // SQL çalıştırma hatası
                    Log::error('SQL execution error', [
                        'message' => $e->getMessage(),
                        'query' => $analysisResult['query'] ?? 'N/A'
                    ]);
                    
                    // Daha açıklayıcı hata mesajları
                    $errorCode = $e->getCode();
                    $errorMsg = $e->getMessage();
                    
                    // Hata mesajındaki genel desenleri tanımla ve kullanıcı dostu mesajlar oluştur
                    if (str_contains($errorMsg, "doesn't exist") || str_contains($errorMsg, "Unknown column")) {
                        $response = "Sorguladığınız veri sistemimizde bulunmuyor. Sorunu daha basit ifade etmeyi veya farklı terimler kullanmayı deneyebilirsiniz.";
                    } elseif (str_contains($errorMsg, "Invalid date") || str_contains($errorMsg, "incorrect datetime value")) {
                        $response = "Belirttiğiniz tarih formatı geçerli değil. Lütfen tarihi 'yıl-ay-gün' (örneğin 2024-05-23) şeklinde belirtin.";
                    } elseif (str_contains($errorMsg, "Division by zero")) {
                        $response = "Hesaplama yaparken sıfıra bölme hatası oluştu. Filtrelediğiniz verilerde bazı değerler eksik olabilir.";
                    } elseif (str_contains($errorMsg, "too complex") || str_contains($errorMsg, "too many tables")) {
                        $response = "Sorgunuz çok karmaşık. Lütfen daha basit bir şekilde bilgi talep edin veya sorgularınızı birkaç parçaya bölün.";
                    } else {
                        // Verilerin bulunmaması durumunda kullanıcı dostu bir mesaj
                        $response = "Bu konuda size yardımcı olabilecek veriler sistemimizde bulunamadı veya sorgunuz net anlaşılamadı. Lütfen sorunuzu daha açık bir şekilde ifade etmeyi deneyin veya farklı bir konuda bilgi talep edin.";
                    }
                }
            } else {
                // SQL gerekmiyorsa normal AI yanıtı al
                $response = $aiAssistant->query(
                    auth()->user(),
                    $messageText,
                    (string) $this->conversation->id
                );
            }
            
            // Yanıt kontrolü
            if (empty(trim($response))) {
                $response = "Üzgünüm, yanıt oluşturulamadı. Lütfen tekrar deneyiniz.";
                Log::warning('API yanıtı boştu, varsayılan mesaj kullanıldı');
            }
            
            // Yanıtı veritabanına kaydet
            $message = $this->conversation->messages()->create([
                'role' => 'assistant',
                'content' => $response
            ]);
            
            // Yazma durumunu kapat
            $this->isTyping = false;
            
            // UI'yi güncelle - mesajı göster
            $this->dispatch('$refresh');
            
            // Input'u aktif et
            $this->isInputDisabled = false;
            
        } catch (\Exception $e) {
            Log::error('AI Response Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'conversation_id' => $this->conversation->id ?? 'unknown'
            ]);
            
            // Hata mesajı gönder
            if ($this->conversation) {
                $errorMessage = 'Üzgünüm, şu anda yanıt veremiyorum. Lütfen daha sonra tekrar deneyin.';
                
                $this->conversation->messages()->create([
                    'role' => 'assistant',
                    'content' => $errorMessage
                ]);
                
                // UI durumunu sıfırla
                $this->isTyping = false;
                $this->isInputDisabled = false;
                
                // Son kullanıcı arayüzü güncellemesi
                $this->dispatch('$refresh');
            }
        }
    }
    
    // Reset the chat UI state
    public function resetUIState()
    {
        $this->isTyping = false;
        $this->isInputDisabled = false;
        $this->currentTypingResponse = null;
        $this->typingMessageId = null;
        
        // Final UI update
        $this->dispatch('$refresh');
    }
    
    /**
     * Mesaj içeriğini getir - JavaScript animasyonu için
     */
    public function getMessageContent($messageId)
    {
        try {
            // Tam yanıt verisini dispatch event'inden alacağız
            // Frontend'den gelen fullResponse değerini döndüreceğiz
            
            Log::info('getMessageContent called for message', ['id' => $messageId]);
            $message = AIMessage::find($messageId);
            
            if (!$message) {
                Log::warning('Message not found', ['id' => $messageId]);
                return null;
            }
            
            // İçerik boş olarak kaydedilmişse, JavaScript'in bize gönderdiği veriyi kullanacağız
            if (empty($message->content)) {
                // JavaScript tarafından gönderilen fullResponse parametresini döndür
                $response = session("ai_response_{$messageId}");
                Log::info('Using session response for animation', ['id' => $messageId, 'found' => !empty($response)]);
                
                if (!empty($response)) {
                    // Yanıtı database'e kaydet
                    $message->update(['content' => $response]);
                    return $response;
                }
            }
            
            // Eğer mesaj içeriği doluysa doğrudan onu döndür
            return $message->content;
            
        } catch (\Exception $e) {
            Log::error('Message content fetch error', [
                'error' => $e->getMessage(),
                'message_id' => $messageId
            ]);
            return null;
        }
    }
    
    /**
     * Animasyon sırasında mesaj içeriğini güncelle
     */
    public function updateMessageContent($messageId, $content)
    {
        try {
            // Mesajı bul ve güncelle
            $message = AIMessage::find($messageId);
            if ($message) {
                $message->update(['content' => $content]);
                Log::info('Message content updated', ['id' => $messageId, 'length' => strlen($content)]);
            }
        } catch (\Exception $e) {
            Log::error('Message update error', [
                'error' => $e->getMessage(),
                'message_id' => $messageId
            ]);
        }
    }
    
    /**
     * JavaScript'ten gelen yanıtı oturum deposuna kaydet
     */
    public function storeResponseInSession($messageId, $fullResponse)
    {
        session(["ai_response_{$messageId}" => $fullResponse]);
        Log::info('Response stored in session', ['id' => $messageId]);
        return true;
    }

    // Start new conversation
    public function startNewConversation()
    {
        // Make current conversation inactive
        if ($this->conversation) {
            $this->conversation->update(['is_active' => false]);
        }
        
        // Create new conversation
        $this->conversation = auth()->user()->aiConversations()->create([
            'title' => 'Yeni Sohbet',
            'is_active' => true
        ]);
        
        // Reset states
        $this->isTyping = false;
        $this->isInputDisabled = false;
        $this->currentTypingResponse = null;
        $this->typingMessageId = null;
        
        // Force refresh
        $this->dispatch('$refresh');
    }
    
    // Get all messages
    public function getMessages()
    {
        if (!$this->conversation) {
            return collect();
        }
        
        return $this->conversation->messages()
            ->orderBy('created_at', 'asc')
            ->get();
    }
    
    // Render
    public function render()
    {
        return view('livewire.ai.chat-widget', [
            'messages' => $this->getMessages()
        ]);
    }
} 