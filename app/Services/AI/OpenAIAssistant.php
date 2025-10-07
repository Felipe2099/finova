    /**
     * Analyze user message and generate an SQL query if needed.
     */
    public function generateSqlQuery($user, string $message, array $databaseSchema): array
    {
        // Cache for token usage
        $cacheKey = 'sql_query_' . md5($message . json_encode($databaseSchema));
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }
        
        // Convert schema to a readable format
        $schemaDescription = $this->formatSchemaForPrompt($databaseSchema);
        
        // Prepare system prompt
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

        // Prepare messages
        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $message]
        ];
        
        try {
            // Send request to API
            $response = $this->client->chat()->create([
                'model' => $this->model,
                'messages' => $messages,
                'temperature' => 0.2, // Lower temperature for determinism
                'response_format' => ['type' => 'json_object'] // Request JSON response
            ]);
            
            // Parse JSON response
            $content = $response->choices[0]->message->content;
            $result = json_decode($content, true);
            
            // Fill missing fields if needed
            if (!isset($result['requires_sql'])) {
                $result['requires_sql'] = false;
            }
            
            if (!isset($result['query'])) {
                $result['query'] = '';
            }
            
            if (!isset($result['explanation'])) {
                $result['explanation'] = 'Analiz sonucu bulunamadı.';
            }
            
            // Cache result (2 hours)
            Cache::put($cacheKey, $result, 7200);
            
            return $result;
        } catch (\Exception $e) {
            Log::error('SQL query generation error', [
                'error' => $e->getMessage(),
                'user_id' => $user->id ?? 'unknown',
                'message' => $message
            ]);
            
            // Default response on error
            return [
                'requires_sql' => false,
                'query' => '',
                'explanation' => 'SQL sorgusu oluşturulamadı: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Create a response using SQL results.
     */
    public function queryWithSqlResults($user, string $message, string $sqlQuery, array $sqlResults, string $conversationId = null): string
    {
        // Cache for token usage
        $cacheKey = 'sql_answer_' . md5($message . $sqlQuery . json_encode($sqlResults) . ($conversationId ?? ''));
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }
        
        // Convert results to a readable format
        $resultsText = $this->formatSqlResultsForPrompt($sqlResults);
        
        // Prepare system prompt
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

        // Build messages
        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $message],
            ['role' => 'assistant', 'content' => "Sorunuzu anlıyorum. Bu bilgiyi almak için bir veritabanı sorgusu çalıştırdım."],
            ['role' => 'user', 'content' => "Çalıştırılan SQL sorgusu:\n```sql\n{$sqlQuery}\n```\n\nSorgu sonuçları:\n```\n{$resultsText}\n```\n\nBu sonuçları analiz ederek bana anlamlı bir yanıt ver. Kullanıcı dostu bir dille açıkla ve önemli noktaları vurgula."]
        ];
        
        try {
            // Send request to API
            $response = $this->client->chat()->create([
                'model' => $this->model,
                'messages' => $messages,
                'temperature' => 0.7, // Higher temperature for more creative explanations
            ]);
            
            $content = $response->choices[0]->message->content;
            
            // Cache result (2 hours)
            Cache::put($cacheKey, $content, 7200);
            
            return $content;
        } catch (\Exception $e) {
            Log::error('SQL results analysis error', [
                'error' => $e->getMessage(),
                'user_id' => $user->id ?? 'unknown',
                'message' => $message,
                'sql_query' => $sqlQuery
            ]);
            
            // Default response on error
            return "Üzgünüm, veritabanı sonuçlarını analiz ederken bir hata oluştu. Lütfen daha sonra tekrar deneyin.";
        }
    }
    
    /**
     * Format the database schema for the prompt.
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
        
        // First 20 rows (or less)
        $limit = min(count($results), 20);
        
        for ($i = 0; $i < $limit; $i++) {
            $row = $results[$i];
            $output .= "Satır " . ($i + 1) . ":\n";
            
            foreach ($row as $key => $value) {
                // Special handling for null values
                if ($value === null) {
                    $value = "NULL";
                }
                // For arrays and objects
                elseif (is_array($value) || is_object($value)) {
                    $value = json_encode($value);
                }
                
                $output .= "  {$key}: {$value}\n";
            }
            
            $output .= "\n";
        }
        
        // If there are more results, indicate that
        if (count($results) > $limit) {
            $remaining = count($results) - $limit;
            $output .= "... ve {$remaining} satır daha (toplam " . count($results) . " satır)";
        } else {
            $output .= "Toplam " . count($results) . " satır.";
        }
        
        return $output;
    } 