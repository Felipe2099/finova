<?php

namespace App\Services\AI\Implementations;

use App\Models\User;
use App\Services\AI\Contracts\AIAssistantInterface;
use App\Services\AI\DateRangeAnalyzer;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Enums\TransactionTypeEnum;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class GeminiAssistant implements AIAssistantInterface
{
    private string $apiKey;
    private string $model;
    private array $config;
    private DateRangeAnalyzer $dateAnalyzer;

    public function __construct(DateRangeAnalyzer $dateAnalyzer)
    {
        $this->apiKey = config('ai.gemini.api_key');
        $this->model = config('ai.gemini.model');
        $this->config = [
            'temperature' => config('ai.gemini.temperature'),
            'maxOutputTokens' => config('ai.gemini.max_tokens'),
        ];
        $this->dateAnalyzer = $dateAnalyzer;
    }

    public function query(User $user, string $question, ?string $conversationId = null): string
    {
        try {
            // Soru içeriğine göre tarih aralığı belirle
            $dateRange = $this->dateAnalyzer->analyze($question);
            
            // Kategori bazlı istatistikleri veritabanı seviyesinde hesapla
            $stats = $this->calculateAggregatedStats($user, $dateRange);
            
            // Detaylı işlem verilerini al
            $transactions = $this->getFilteredTransactions($user, $dateRange);
            
            // Hassas verileri maskele ve veriyi hazırla
            $sanitizedData = $this->sanitizeData($transactions);
            
            // Sohbet geçmişini al
            $history = $this->getConversationHistory($conversationId);
            
            // Gemini prompt'unu hazırla
            $prompt = $this->buildPrompt($question, $sanitizedData, $stats, $dateRange, $history);

            // Gemini API çağrısı
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'x-goog-api-key' => $this->apiKey,
            ])->post("https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent", [
                'contents' => $this->formatContents(config('ai.system_prompt'), $prompt, $history),
                'generationConfig' => $this->config
            ]);

            if ($response->successful()) {
                $result = $response->json();
                return $this->formatResponse($result['candidates'][0]['content']['parts'][0]['text'] ?? 
                       'Üzgünüm, yanıt oluşturulurken bir hata oluştu.');
            }

            Log::error('Gemini API Error', [
                'error' => $response->json(),
                'status' => $response->status()
            ]);

            return 'Üzgünüm, bir hata oluştu. Lütfen tekrar deneyin.';

        } catch (\Exception $e) {
            Log::error('Gemini Assistant Error', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
                'question' => $question,
                'conversation_id' => $conversationId
            ]);

            return 'Üzgünüm, bir hata oluştu. Lütfen tekrar deneyin.';
        }
    }

    /**
     * Belirtilen sohbet ID'sine ait mesajları token limitini aşmamaya
     * çalışarak veritabanından alır.
     *
     * @param string|null $conversationId
     * @param int $maxTokenEstimate Maksimum token tahmini limiti
     * @return array Geçmiş mesajlar dizisi (role, content).
     */
    private function getConversationHistory(?string $conversationId, int $maxTokenEstimate = 2000): array
    {
        if (!$conversationId) {
            return [];
        }

        // Sohbete ait son 10 mesajı al (bağlam için yeterli olacaktır)
        $allMessages = DB::table('ai_messages')
            ->where('conversation_id', $conversationId)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get(['id', 'role', 'content', 'created_at']);
        
        // Eğer mesaj yoksa boş dizi döndür
        if ($allMessages->isEmpty()) {
            return [];
        }
        
        $result = [];
        $estimatedTokens = 0;
        $tokenPerCharEstimate = 0.25; // Ortalama karakter başına token tahmini

        // En son mesajdan başlayarak mesajları ekle, token limitini aşmamaya çalış
        foreach ($allMessages as $message) {
            // Basit token tahmini (daha doğru tahmin için tokenizer kullanılabilir)
            $contentLength = mb_strlen($message->content);
            $estimatedMessageTokens = (int)($contentLength * $tokenPerCharEstimate);
            
            // Token limitini aşacaksa döngüden çık
            if ($estimatedTokens + $estimatedMessageTokens > $maxTokenEstimate) {
                break;
            }
            
            // Mesajı sonuç dizisinin başına ekle (eskiden yeniye doğru sıralamak için)
            array_unshift($result, [
                'role' => $message->role === 'ai' ? 'assistant' : $message->role,
                'content' => $message->content
            ]);
            
            $estimatedTokens += $estimatedMessageTokens;
        }
        
        // En azından son mesajı her zaman ekle (önemli bağlam için)
        if (empty($result) && !$allMessages->isEmpty()) {
            $lastMessage = $allMessages->first();
            $result[] = [
                'role' => $lastMessage->role === 'ai' ? 'assistant' : $lastMessage->role,
                'content' => $lastMessage->content
            ];
        }
        
        return $result;
    }

    /**
     * Gemini API için contents formatını hazırlar.
     * 
     * @param string $systemPrompt Sistem prompt'u
     * @param string $userPrompt Kullanıcı prompt'u
     * @param array $history Geçmiş mesajlar
     * @return array Gemini API'si için contents dizisi
     */
    private function formatContents(string $systemPrompt, string $userPrompt, array $history = []): array
    {
        $contents = [];
        
        // Sistem prompt'u ve kullanıcı prompt'unu ekle
        $contents[] = [
            'role' => 'user',
            'parts' => [
                ['text' => $systemPrompt]
            ]
        ];
        
        // Eğer sohbet geçmişi varsa ekle
        if (!empty($history)) {
            foreach ($history as $message) {
                if (!empty($message['role']) && !empty($message['content'])) {
                    $role = ($message['role'] === 'assistant') ? 'model' : 'user';
                    $contents[] = [
                        'role' => $role,
                        'parts' => [
                            ['text' => $message['content']]
                        ]
                    ];
                }
            }
        }
        
        // Son kullanıcı sorusunu ekle
        $contents[] = [
            'role' => 'user',
            'parts' => [
                ['text' => $userPrompt]
            ]
        ];
        
        return $contents;
    }

    private function calculateAggregatedStats(User $user, array $dateRange): array
    {
        $stats = [];
        $summary = [
            'income_total' => 0.0,
            'expense_total' => 0.0,
            'net_total' => 0.0
        ];
        
        $categoryStats = DB::table('transactions')
            ->join('categories', 'transactions.category_id', '=', 'categories.id')
            ->whereBetween('transactions.date', [$dateRange['start'], $dateRange['end']])
            ->whereIn('transactions.type', [TransactionTypeEnum::INCOME->value, TransactionTypeEnum::EXPENSE->value])
            ->select(
                'categories.name as category',
                'transactions.type',
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(CASE WHEN transactions.try_equivalent IS NOT NULL THEN transactions.try_equivalent ELSE transactions.amount END) as total'),
                DB::raw('AVG(CASE WHEN transactions.try_equivalent IS NOT NULL THEN transactions.try_equivalent ELSE transactions.amount END) as average'),
                DB::raw("DATE_FORMAT(date, '%Y-%m') as month")
            )
            ->groupBy('categories.name', 'transactions.type', 'month')
            ->get();

        foreach ($categoryStats->groupBy('category') as $category => $types) {
            foreach ($types->groupBy('type') as $type => $months) {
                $totalAmount = $months->sum('total');
                $totalCount = $months->sum('count');
                
                $stats[$category][$type] = [
                    'toplam' => $this->formatCurrency($totalAmount),
                    'işlem_sayısı' => $totalCount,
                    'ortalama' => $totalCount > 0 ? $this->formatCurrency($totalAmount / $totalCount) : $this->formatCurrency(0),
                    'aylık_detay' => $months->mapWithKeys(function ($data) {
                        return [
                            $data->month => [
                                'toplam' => $this->formatCurrency($data->total),
                                'işlem_sayısı' => $data->count
                            ]
                        ];
                    })->toArray()
                ];
            }
        }

        $stats['summary'] = [
            'income_total' => $this->formatCurrency($summary['income_total']),
            'expense_total' => $this->formatCurrency($summary['expense_total']),
            'net_total' => $this->formatCurrency($summary['net_total'])
        ];

        return $stats;
    }

    /**
     * Para birimini formatla
     */
    private function formatCurrency(?float $amount): string
    {
        if ($amount === null) {
            return '0,00 TL';
        }
        return number_format(abs($amount), 2, ',', '.') . ' TL';
    }

    private function getFilteredTransactions(User $user, array $dateRange): \Illuminate\Support\Collection
    {
        return DB::table('transactions')
            ->join('categories', 'transactions.category_id', '=', 'categories.id')
            ->whereBetween('transactions.date', [$dateRange['start'], $dateRange['end']])
            ->whereIn('transactions.type', [TransactionTypeEnum::INCOME->value, TransactionTypeEnum::EXPENSE->value])
            ->orderBy('transactions.date', 'desc')
            ->get();
    }

    private function sanitizeData($transactions): array
    {
        return $transactions->map(function ($transaction) {
            // Type değeri string veya enum olabilir, güvenli şekilde değeri al
            $type = is_object($transaction->type) && method_exists($transaction->type, 'value') 
                ? $transaction->type->value 
                : (string) $transaction->type;
                
            // Tutarlılık için try_equivalent kullan
            $amount = $transaction->try_equivalent ?? $transaction->amount;
            
            // String tarihi Carbon'a çevir ve formatla
            $date = $transaction->date instanceof Carbon 
                ? $transaction->date 
                : Carbon::parse($transaction->date);
                
            return [
                'type' => $type,
                'amount' => $this->formatCurrency($amount),
                'category' => $transaction->category ?? 'Kategorisiz',
                'date' => $date->format('d.m.Y'),
                'description' => $this->maskSensitiveData($transaction->description)
            ];
        })->toArray();
    }

    private function buildPrompt(string $question, array $data, array $stats, array $dateRange, array $history): string
    {
        $prompt = "Soru: {$question}\n\n";
        $prompt .= "Analiz Edilen Dönem:\n";
        $prompt .= "- Başlangıç: " . Carbon::parse($dateRange['start'])->format('d.m.Y') . "\n";
        $prompt .= "- Bitiş: " . Carbon::parse($dateRange['end'])->format('d.m.Y') . "\n";
        $prompt .= "- Dönem Tipi: " . $this->getPeriodTypeText($dateRange['period_type']) . "\n\n";
        
        $prompt .= "Önemli Notlar:\n";
        $prompt .= "1. Lütfen yanıtlarında Türk Lirası tutarlarını '.' binlik ayracı ve ',' ondalık ayracı ile formatla (Örnek: 1.234,56 TL)\n";
        $prompt .= "2. Sadece soru sahibinin kendi verileri gösterilmektedir.\n";
        $prompt .= "3. Transfer işlemleri hesaplamalara dahil edilmemiştir.\n";
        $prompt .= "4. Elinde olmayan veriyle ilgili sorularda, sadece mevcut veriler üzerinden analiz yap ve eksik olanı belirt. Eğer kullanıcı örneğin kredi kartı komisyonu gibi bir veri sorarsa ve bu veri yoksa, 'Bu konuda elimde veri yok, sadece mevcut işlemler üzerinden analiz yapabilirim.' şeklinde cevap ver.\n\n";
        
        $prompt .= "Kategori Bazlı İstatistikler:\n" . json_encode($stats, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";
        $prompt .= "İşlemler:\n" . json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        
        return $prompt;
    }

    private function getPeriodTypeText(string $type): string
    {
        return match($type) {
            'month' => 'Aylık',
            'year' => 'Yıllık',
            'custom' => 'Özel Dönem',
            default => 'Belirsiz'
        };
    }

    private function formatResponse(string $response): string
    {
        return $response;
    }

    private function maskSensitiveData(?string $text): string
    {
        if (!$text) return '';
        
        $patterns = [
            '/\b\d{16}\b/' => '****-****-****-****',
            '/\bTR\d{24}\b/i' => 'TR**-****-****-****-****-****',
            '/\b\d{11}\b/' => '*****',
        ];

        return preg_replace(array_keys($patterns), array_values($patterns), $text);
    }
} 