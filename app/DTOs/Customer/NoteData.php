<?php

declare(strict_types=1);

namespace App\DTOs\Customer;

/**
 * Müşteri Notu Veri Transfer Nesnesi
 * 
 * Müşteri notlarının taşınması ve dönüştürülmesi için kullanılan DTO sınıfı.
 * Müşteri notları oluşturma ve görüntüleme işlemlerinde kullanılır.
 */
class NoteData
{
    /**
     * @param int $customer_id Müşteri ID
     * @param string $type Not türü
     * @param string $content Not içeriği
     * @param string $activity_date Aktivite tarihi
     * @param int|null $user_id Kullanıcı ID
     * @param int|null $assigned_user_id Atanan kullanıcı ID
     */
    public function __construct(
        public readonly int $customer_id,
        public readonly string $type,
        public readonly string $content,
        public readonly string $activity_date,
        public readonly ?int $user_id = null,
        public readonly ?int $assigned_user_id = null,
    ) {}

    /**
     * Dizi verisinden not verisi oluşturur
     * 
     * @param array $data Not verileri dizisi
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            customer_id: (int) $data['customer_id'],
            type: $data['type'],
            content: $data['content'],
            activity_date: $data['activity_date'],
            user_id: $data['user_id'] ? (int) $data['user_id'] : null,
            assigned_user_id: $data['assigned_user_id'] ? (int) $data['assigned_user_id'] : null,
        );
    }

    /**
     * Not verisini dizi formatına dönüştürür
     * 
     * @return array
     */
    public function toArray(): array
    {
        return [
            'customer_id' => $this->customer_id,
            'type' => $this->type,
            'content' => $this->content,
            'activity_date' => $this->activity_date,
            'user_id' => $this->user_id,
            'assigned_user_id' => $this->assigned_user_id,
        ];
    }
} 