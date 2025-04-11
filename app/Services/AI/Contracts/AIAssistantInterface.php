<?php

namespace App\Services\AI\Contracts;

use App\Models\User;

interface AIAssistantInterface
{
    /**
     * Kullanıcının sorusunu AI modeline gönderir ve yanıtı alır.
     *
     * @param User $user Soruyu soran kullanıcı.
     * @param string $question Kullanıcının sorusu.
     * @param string|null $conversationId Mevcut sohbetin ID'si (varsa).
     * @return string AI modelinin yanıtı.
     */
    public function query(User $user, string $question, ?string $conversationId = null): string;
    
    /**
     * Kullanıcı mesajını analiz edip SQL sorgusu üretir
     * 
     * @param mixed $user Kullanıcı objesi
     * @param string $message Kullanıcı mesajı
     * @param array $databaseSchema Veritabanı şeması (tablo ve alan bilgileri)
     * @return array ['query' => string, 'requires_sql' => bool, 'explanation' => string]
     */
    public function generateSqlQuery($user, string $message, array $databaseSchema): array;
    
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
    public function queryWithSqlResults($user, string $message, string $sqlQuery, array $sqlResults, string $conversationId = null): string;
} 