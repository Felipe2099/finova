<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\QueryException;
use App\Services\AI\Exceptions\UnsafeSqlException;

class SqlQueryService
{
    /**
     * İzin verilen SQL komutlar
     */
    protected const ALLOWED_COMMANDS = ['SELECT'];
    
    /**
     * Yasaklanmış SQL anahtar kelimeleri
     */
    protected const FORBIDDEN_KEYWORDS = [
        'INSERT', 'UPDATE', 'DELETE', 'DROP', 'ALTER', 'TRUNCATE', 
        'CREATE', 'RENAME', 'EXEC', 'EXECUTE', 'STORED PROCEDURE',
        'GRANT', 'REVOKE', 'COMMIT', 'ROLLBACK', 'SAVEPOINT', 'MERGE',
        'CALL', 'CONNECT', 'LOCK', 'PREPARE', 'DEALLOCATE', 'SET',
        'EXPLAIN', 'ANALYZE'
    ];
    
    /**
     * SQL sorgusunu doğrula
     * 
     * @param string $query
     * @return bool
     * @throws UnsafeSqlException
     */
    public function validateQuery(string $query): bool
    {
        $normalizedQuery = strtoupper(trim($query));
        
        // Sadece SELECT işlemlerine izin ver
        $hasAllowedStart = false;
        foreach (self::ALLOWED_COMMANDS as $command) {
            if (strpos($normalizedQuery, $command) === 0) {
                $hasAllowedStart = true;
                break;
            }
        }
        
        if (!$hasAllowedStart) {
            throw new UnsafeSqlException("SQL sorgusu izin verilen bir komutla başlamalıdır.");
        }
        
        // Yasaklı anahtar kelimeleri kontrol et
        foreach (self::FORBIDDEN_KEYWORDS as $keyword) {
            if (preg_match('/\b' . preg_quote($keyword) . '\b/i', $normalizedQuery)) {
                throw new UnsafeSqlException("SQL sorgusunda güvenli olmayan anahtar kelime bulundu: {$keyword}");
            }
        }
        
        // Çoklu sorgu kontrolü - tek bir sorguya izin ver
        if (strpos($normalizedQuery, ';') !== false) {
            // İstisnai durum: son karakter noktalı virgül ise izin ver
            $lastChar = substr(trim($normalizedQuery), -1);
            if ($lastChar === ';') {
                // Sorun yok, son karakter noktalı virgül
            } else if (substr_count($normalizedQuery, ';') > 1) {
                throw new UnsafeSqlException("Birden fazla SQL sorgusu çalıştırılamaz.");
            }
        }
        
        return true;
    }
    
    /**
     * SQL sorgusunu çalıştır
     * 
     * @param string $query
     * @return array
     * @throws UnsafeSqlException
     * @throws QueryException
     */
    public function executeQuery(string $query): array
    {
        $this->validateQuery($query);
        
        try {
            Log::info('Executing SQL query', ['query' => $query]);
            
            // İstenirse buraya ilave güvenlik önlemleri eklenebilir
            $results = DB::select($query);
            
            // Sonuçları indeksli array'e dönüştür
            $results = json_decode(json_encode($results), true);
            
            Log::info('SQL query executed successfully', [
                'query' => $query,
                'results_count' => count($results)
            ]);
            
            return $results;
        } catch (QueryException $e) {
            Log::error('SQL query execution error', [
                'query' => $query,
                'error' => $e->getMessage(),
                'code' => $e->getCode()
            ]);
            
            throw $e;
        }
    }
} 