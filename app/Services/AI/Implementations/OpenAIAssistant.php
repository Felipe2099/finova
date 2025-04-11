<?php

namespace App\Services\AI\Implementations;

use App\Models\User;
use App\Services\AI\Contracts\AIAssistantInterface;
use App\Services\AI\DateRangeAnalyzer;
use Illuminate\Support\Facades\Log;
use OpenAI\Client;
use App\Enums\TransactionTypeEnum;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class OpenAIAssistant implements AIAssistantInterface
{
    public function __construct(
        private readonly Client $openai,
        private readonly DateRangeAnalyzer $dateAnalyzer
    ) {}

    /**
     * Kullanıcının sorusunu AI modeline gönderir ve yanıtı alır.
     *
     * @param User $user Soruyu soran kullanıcı.
     * @param string $question Kullanıcının sorusu.
     * @param string|null $conversationId Mevcut sohbetin ID'si (varsa).
     * @return string AI modelinin yanıtı.
     */
    public function query(User $user, string $question, ?string $conversationId = null): string
    {
        try {
            // Soru içeriğine göre tarih aralığı belirle
            $dateRange = $this->dateAnalyzer->analyze($question);
            
            // Kategori bazlı istatistikleri ve genel toplamları hesapla
            $stats = $this->calculateAggregatedStats($user, $dateRange);
            
            // Detaylı işlem verilerini al
            $transactions = $this->getFilteredTransactions($user, $dateRange);
            
            // Hassas verileri maskele ve veriyi hazırla
            $sanitizedData = $this->sanitizeData($transactions);
            
            // Sohbet geçmişini al
            $history = $this->getConversationHistory($conversationId);

            // AI prompt'unu geçmişle birlikte mesaj dizisi olarak hazırla
            $messages = $this->buildPrompt(
                $question,
                $sanitizedData,
                $stats, 
                $dateRange,
                $history
            );
            
            // OpenAI API çağrısı yap
            $response = $this->openai->chat()->create([
                'model' => config('ai.openai.model'),
                'messages' => $messages, // Doğrudan mesaj dizisini gönder
                'temperature' => (float) config('ai.openai.temperature'),
                'max_tokens' => (int) config('ai.openai.max_tokens')
            ]);

            return $response->choices[0]->message->content;

        } catch (\Exception $e) {
            Log::error('OpenAI API Error', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
                'question' => $question,
                'conversation_id' => $conversationId
            ]);

            if (str_contains($e->getMessage(), 'api_key')) {
                return 'API anahtarı ile ilgili bir sorun oluştu. Lütfen sistem yöneticisi ile iletişime geçin.';
            }

            if (str_contains($e->getMessage(), 'rate_limit')) {
                return 'Çok fazla istek gönderildi. Lütfen biraz bekleyip tekrar deneyin.';
            }

            return 'Üzgünüm, bir hata oluştu. Lütfen tekrar deneyin. Hata devam ederse sistem yöneticisi ile iletişime geçin.';
        }
    }

    /**
     * Belirtilen tarih aralığı ve kullanıcı için kategori bazlı ve genel istatistikleri hesaplar.
     *
     * @param User $user
     * @param array $dateRange
     * @return array Hesaplanan istatistikler (kategori detayları ve genel toplamlar).
     */
    private function calculateAggregatedStats(User $user, array $dateRange): array
    {
        $stats = [];
        $summary = [
            'income_total' => 0.0,
            'expense_total' => 0.0,
            'net_total' => 0.0,
            'commission_total' => 0.0,
            'commission_paid' => 0.0,
            'commission_remaining' => 0.0
        ];

        // Kategori bazlı toplam ve ortalama hesapla
        $categoryStats = DB::table('transactions')
            ->join('categories', 'transactions.category_id', '=', 'categories.id')
            ->whereBetween('transactions.date', [$dateRange['start'], $dateRange['end']])
            ->whereIn('transactions.type', [TransactionTypeEnum::INCOME->value, TransactionTypeEnum::EXPENSE->value])
            ->select(
                'categories.name as category',
                'categories.type as category_type',
                'transactions.type',
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(CASE WHEN transactions.try_equivalent IS NOT NULL THEN transactions.try_equivalent ELSE transactions.amount END) as total'),
                DB::raw('AVG(CASE WHEN transactions.try_equivalent IS NOT NULL THEN transactions.try_equivalent ELSE transactions.amount END) as average'),
                DB::raw("DATE_FORMAT(date, '%Y-%m') as month")
            )
            ->groupBy('categories.name', 'categories.type', 'transactions.type', 'month')
            ->get();

        // Genel Toplamları Hesapla
        $incomeTotal = DB::table('transactions')
            ->whereBetween('date', [$dateRange['start'], $dateRange['end']])
            ->where('type', TransactionTypeEnum::INCOME->value)
            ->sum(DB::raw('CASE WHEN try_equivalent IS NOT NULL THEN try_equivalent ELSE amount END'));
        
        $expenseTotal = DB::table('transactions')
            ->whereBetween('date', [$dateRange['start'], $dateRange['end']])
            ->where('type', TransactionTypeEnum::EXPENSE->value)
            ->sum(DB::raw('CASE WHEN try_equivalent IS NOT NULL THEN try_equivalent ELSE amount END'));

        // Komisyon hesaplamaları
        // 1. Toplam kazanılan komisyon (ödenmiş + ödenmemiş)
        $commissionTotal = DB::table('commissions')
            ->where('status', 'approved')  // Sadece onaylanmış komisyonları topla
            ->sum('commission_amount');

        // 2. Ödenmiş komisyonlar
        $commissionPaid = DB::table('commission_payouts')
            ->where('status', 'completed')  // Sadece tamamlanmış ödemeleri topla
            ->sum('amount');

        // 3. Bekleyen ödemeler (onaylanmış ama ödenmemiş)
        $commissionPending = $commissionTotal - $commissionPaid;

        $summary['income_total'] = (float) $incomeTotal;
        $summary['expense_total'] = abs((float) $expenseTotal);
        $summary['net_total'] = $summary['income_total'] - $summary['expense_total'];
        $summary['commission_total'] = (float) $commissionTotal;
        $summary['commission_paid'] = (float) $commissionPaid;
        $summary['commission_remaining'] = (float) $commissionPending;

        // İstatistikleri düzenle
        foreach ($categoryStats->groupBy('category') as $category => $dataForCategory) {
            $categoryType = $dataForCategory->first()->category_type;
            $totalAmount = $dataForCategory->sum('total');
            $totalCount = $dataForCategory->sum('count');
                
            $stats[$categoryType][$category] = [
                'toplam' => $this->formatCurrency($totalAmount),
                'işlem_sayısı' => $totalCount,
                'ortalama' => $totalCount > 0 ? $this->formatCurrency($totalAmount / $totalCount) : $this->formatCurrency(0),
                'aylık_detay' => $dataForCategory->mapWithKeys(function ($data) {
                    return [
                        $data->month => [
                            'toplam' => $this->formatCurrency($data->total),
                            'işlem_sayısı' => $data->count
                        ]
                    ];
                })->toArray()
            ];
        }
        
        $stats['summary'] = [
            'income_total' => $this->formatCurrency($summary['income_total']),
            'expense_total' => $this->formatCurrency($summary['expense_total']),
            'net_total' => $this->formatCurrency($summary['net_total']),
            'commission_total' => $this->formatCurrency($summary['commission_total']),
            'commission_paid' => $this->formatCurrency($summary['commission_paid']),
            'commission_remaining' => $this->formatCurrency($summary['commission_remaining'])
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

    /**
     * Belirtilen sohbet ID'sine ait son N mesajı veritabanından alır.
     * Token limitini aşmamak için dinamik bir yaklaşım kullanır.
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

    /**
     * OpenAI için prompt'u mesaj dizisi formatında oluşturur.
     *
     * @param string $question
     * @param array $data İşlenmiş işlem verileri.
     * @param array $stats İşlenmiş istatistik verileri (genel özet dahil).
     * @param array $dateRange
     * @param array $history Geçmiş mesajlar.
     * @return array OpenAI API'si için mesaj dizisi.
     */
    private function buildPrompt(
        string $question,
        array $data,
        array $stats,
        array $dateRange,
        array $history = []
    ): array // Dizi dönecek
    {
        $messages = [];

        // 1. Sistem Prompt'u
        $messages[] = ['role' => 'system', 'content' => config('ai.system_prompt')];

        // 2. Konuşma Geçmişi
        if (!empty($history)) {
            foreach ($history as $message) {
                // Geçerli bir rol ve içerik kontrolü
                if (!empty($message['role']) && !empty($message['content'])) {
                    $messages[] = $message;
                }
            }
        }

        // 3. Mevcut Soru ve Bağlam Verisi
        $currentUserContent = "Kullanıcı Sorusu: {$question}\n\n"; // Soruyu daha belirgin yap
        $currentUserContent .= "### Analiz Edilen Dönem ve Özet Bilgiler\n"; // Markdown başlık
        $currentUserContent .= "- Başlangıç Tarihi: " . Carbon::parse($dateRange['start'])->format('d.m.Y') . "\n";
        $currentUserContent .= "- Bitiş Tarihi: " . Carbon::parse($dateRange['end'])->format('d.m.Y') . "\n";
        $currentUserContent .= "- Dönem Tipi: " . $this->getPeriodTypeText($dateRange['period_type']) . "\n";
        $currentUserContent .= "- Toplam Gelir: " . ($stats['summary']['income_total'] ?? 'Hesaplanamadı') . "\n";
        $currentUserContent .= "- Toplam Gider: " . ($stats['summary']['expense_total'] ?? 'Hesaplanamadı') . "\n";
        $currentUserContent .= "- Net Durum: " . ($stats['summary']['net_total'] ?? 'Hesaplanamadı') . "\n\n";

        $currentUserContent .= "### Önemli Notlar\n";
        $currentUserContent .= "1. Yanıtlarda Türk Lirası tutarlarını '.' binlik ayracı ve ',' ondalık ayracı ile formatla (Örnek: 1.234,56 TL).\n";
        $currentUserContent .= "2. Sadece soru sahibinin kendi verileri gösterilmektedir.\n";
        $currentUserContent .= "3. Transfer işlemleri analize dahil edilmemiştir.\n\n";
        
        // Kategori istatistiklerini ayrı bir bölümde sun
        $categoryStatsForPrompt = $stats; // Orijinali kopyala
        unset($categoryStatsForPrompt['summary']); // Özeti çıkar
        $currentUserContent .= "### Kategori Bazlı İstatistikler\n" . json_encode($categoryStatsForPrompt, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_FORCE_OBJECT) . "\n\n"; // JSON_FORCE_OBJECT boşsa {} döner
        
        // İşlemleri ayrı bir bölümde sun (belki limitli?)
        $currentUserContent .= "### Döneme Ait İşlemler (Örnekler)\n" . json_encode(array_slice($data, 0, 20), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE); // Örn: Son 20 işlem

        $messages[] = ['role' => 'user', 'content' => $currentUserContent];

        return $messages;
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

    private function maskSensitiveData(?string $text): string
    {
        if (!$text) return '';
        
        $patterns = [
            '/\b\d{16}\b/' => '****-****-****-****',
            '/\bTR\d{24}\b/i' => 'TR**-****-****-****-****-****',
            // TC Kimlik No gibi hassas olabilecekleri de ekle:
            '/(?<!\d)\d{11}(?!\d)/' => '***********',
        ];

        return preg_replace(array_keys($patterns), array_values($patterns), $text);
    }

    /**
     * Kullanıcı mesajını analiz edip SQL sorgusu üretir
     * 
     * @param mixed $user Kullanıcı objesi
     * @param string $message Kullanıcı mesajı
     * @param array $databaseSchema Veritabanı şeması (tablo ve alan bilgileri)
     * @return array ['query' => string, 'requires_sql' => bool, 'explanation' => string]
     */
    public function generateSqlQuery($user, string $message, array $databaseSchema): array
    {
        try {
            // Token kullanımı için cache
            $cacheKey = 'sql_query_' . md5($message . json_encode($databaseSchema));
            if (\Illuminate\Support\Facades\Cache::has($cacheKey)) {
                return \Illuminate\Support\Facades\Cache::get($cacheKey);
            }
            
            // Şemayı okunaklı formata çevir
            $schemaDescription = $this->formatSchemaForPrompt($databaseSchema);
            
            // Sistem prompt'u hazırla
            $systemPrompt = <<<EOT
Sen bir veritabanı sorguları oluşturan AI asistanısın. Görevin, kullanıcının doğal dil sorgusunu analiz etmek ve eğer gerekiyorsa uygun bir SQL sorgusu oluşturmaktır. 
Sadece SELECT sorgularını oluşturabilirsin.

Şu veritabanı şeması hakkında bilgin var:
{$schemaDescription}

Kurallara uymalısın:
1. Kullanıcı finansal verileri, işlemleri, müşterileri, borçları vb. hakkında soru soruyorsa, uygun bir SQL sorgusu oluştur.
2. Kullanıcı genel site kullanımı, siteyle ilgili bilgi veya veritabanıyla ilgisi olmayan konular hakkında soru soruyorsa, SQL sorgusu OLUŞTURMA.
3. Kullanıcı veri değiştirme, ekleme, silme isteğinde bulunursa, SQL sorgusu OLUŞTURMA ve bunu yapamayacağını belirt.
4. SQL sorgularında sadece SELECT ifadelerini kullan, hiçbir şekilde INSERT, UPDATE, DELETE, DROP veya diğer veri değiştiren ifadeleri kullanma.
5. İlgili tablolar arasında JOIN yaparak ilişkili verileri sorgulayabilirsin.
6. Tablo ve sütun isimlerini tam olarak veritabanı şemasında belirtildiği gibi kullan.
7. Oluşturduğun sorgu MySQL'de çalışacak şekilde olmalıdır.

Yanıtın şu formatta olmalıdır:
{
  "requires_sql": boolean,  // SQL sorgusu gerekli mi?
  "query": string,          // SQL sorgusu (requires_sql true ise)
  "explanation": string     // Sorgunu veya SQL sorgusu oluşturmama nedenini açıkla
}
EOT;

            // Mesajları hazırla
            $messages = [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $message]
            ];
            
            // API'ye istek gönder
            $response = $this->openai->chat()->create([
                'model' => config('ai.openai.model'),
                'messages' => $messages,
                'temperature' => 0.2, // Daha deterministik sonuçlar için düşük sıcaklık
                'response_format' => ['type' => 'json_object'] // JSON formatında yanıt iste
            ]);
            
            // JSON yanıtı parse et
            $content = $response->choices[0]->message->content;
            $result = json_decode($content, true);
            
            // Eksik alanlar varsa doldur
            if (!isset($result['requires_sql'])) {
                $result['requires_sql'] = false;
            }
            
            if (!isset($result['query'])) {
                $result['query'] = '';
            }
            
            if (!isset($result['explanation'])) {
                $result['explanation'] = 'Analiz sonucu bulunamadı.';
            }
            
            // Sonucu cache'le (2 saat)
            \Illuminate\Support\Facades\Cache::put($cacheKey, $result, 7200);
            
            return $result;
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('SQL query generation error', [
                'error' => $e->getMessage(),
                'user_id' => $user->id ?? 'unknown',
                'message' => $message
            ]);
            
            // Hata durumunda varsayılan yanıt
            return [
                'requires_sql' => false,
                'query' => '',
                'explanation' => 'SQL sorgusu oluşturulamadı: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * SQL sonuçlarını kullanarak yanıt oluşturur
     * 
     * @param mixed $user Kullanıcı objesi
     * @param string $message Kullanıcı mesajı
     * @param string $sqlQuery Çalıştırılan SQL sorgusu
     * @param array $sqlResults SQL sonuçları
     * @param string $conversationId Konuşma ID'si
     * @return string
     */
    public function queryWithSqlResults($user, string $message, string $sqlQuery, array $sqlResults, string $conversationId = null): string
    {
        try {
            // Token kullanımı için cache
            $cacheKey = 'sql_answer_' . md5($message . $sqlQuery . json_encode($sqlResults) . ($conversationId ?? ''));
            if (\Illuminate\Support\Facades\Cache::has($cacheKey)) {
                return \Illuminate\Support\Facades\Cache::get($cacheKey);
            }
            
            // Sonuçları okunaklı formata çevir
            $resultsText = $this->formatSqlResultsForPrompt($sqlResults);
            
            // Sistem promptu hazırla
            $systemPrompt = <<<EOT
Sen bir finansal veri analistine yardımcı olan AI asistanısın. Kullanıcının sorusuna yanıt vermek için bir SQL sorgusu çalıştırıldı.
Görevin, SQL sorgu sonuçlarını inceleyerek ve kullanıcının orijinal sorusunu dikkate alarak anlamlı, açıklayıcı bir yanıt oluşturmaktır.

Yanıtın şunları içermelidir:
1. Sorunun doğrudan yanıtı
2. Varsa önemli eğilimler, desenler veya gözlemler
3. Gerekiyorsa sorgu sonuçlarından çıkarılan önemli bilgiler
4. Türkçe ve anlaşılır bir dil kullan

Kullanıcıya çıplak SQL sorgusu veya teknik jargon gösterme. Sonuçları anlamlı bir analize dönüştür.

Mevcut konuşma geçmişini ve kullanıcının ilgi alanlarını dikkate al. Yanıtın uzunluğu sorunun karmaşıklığına uygun olmalıdır.
EOT;

            // Mesajları hazırla
            $messages = [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $message],
                ['role' => 'assistant', 'content' => "Sorunuzu anlıyorum. Bu bilgiyi almak için bir veritabanı sorgusu çalıştırdım."],
                ['role' => 'user', 'content' => "Çalıştırılan SQL sorgusu:\n```sql\n{$sqlQuery}\n```\n\nSorgu sonuçları:\n```\n{$resultsText}\n```\n\nBu sonuçları analiz ederek bana anlamlı bir yanıt ver. Kullanıcı dostu bir dille açıkla ve önemli noktaları vurgula."]
            ];
            
            // API'ye istek gönder
            $response = $this->openai->chat()->create([
                'model' => config('ai.openai.model'),
                'messages' => $messages,
                'temperature' => 0.7, // Daha yaratıcı açıklamalar için sıcaklığı arttır
            ]);
            
            $content = $response->choices[0]->message->content;
            
            // Sonucu cache'le (2 saat)
            \Illuminate\Support\Facades\Cache::put($cacheKey, $content, 7200);
            
            return $content;
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('SQL results analysis error', [
                'error' => $e->getMessage(),
                'user_id' => $user->id ?? 'unknown',
                'message' => $message,
                'sql_query' => $sqlQuery
            ]);
            
            // Hata durumunda varsayılan yanıt
            return "Üzgünüm, veritabanı sonuçlarını analiz ederken bir hata oluştu. Lütfen daha sonra tekrar deneyin.";
        }
    }
    
    /**
     * Veritabanı şemasını prompt için formatla
     */
    protected function formatSchemaForPrompt(array $schema): string
    {
        $output = "Tablolar ve alanlar:\n\n";
        
        foreach ($schema['tables'] as $tableName => $columns) {
            $output .= "- {$tableName}\n";
            
            foreach ($columns as $columnName => $columnType) {
                $output .= "  - {$columnName}: {$columnType}\n";
            }
            
            $output .= "\n";
        }
        
        $output .= "İlişkiler:\n\n";
        
        foreach ($schema['relationships'] as $relation) {
            $output .= "- {$relation['source_table']}.{$relation['source_column']} -> {$relation['target_table']}.{$relation['target_column']} ({$relation['type']})\n";
        }
        
        return $output;
    }
    
    /**
     * SQL sonuçlarını prompt için formatla
     */
    protected function formatSqlResultsForPrompt(array $results): string
    {
        if (empty($results)) {
            return "Sonuç bulunamadı.";
        }
        
        $output = "";
        
        // İlk 20 satır (veya daha az)
        $limit = min(count($results), 20);
        
        for ($i = 0; $i < $limit; $i++) {
            $row = $results[$i];
            $output .= "Satır " . ($i + 1) . ":\n";
            
            foreach ($row as $key => $value) {
                // null değerler için özel işlem
                if ($value === null) {
                    $value = "NULL";
                }
                // Diziler ve nesneler için
                elseif (is_array($value) || is_object($value)) {
                    $value = json_encode($value);
                }
                
                $output .= "  {$key}: {$value}\n";
            }
            
            $output .= "\n";
        }
        
        // Eğer daha fazla sonuç varsa, bunu belirt
        if (count($results) > $limit) {
            $remaining = count($results) - $limit;
            $output .= "... ve {$remaining} satır daha (toplam " . count($results) . " satır)";
        } else {
            $output .= "Toplam " . count($results) . " satır.";
        }
        
        return $output;
    }
} 