<?php

declare(strict_types=1);

namespace App\DTOs\CustomerGroup;

/**
 * Müşteri Grubu Veri Transfer Nesnesi
 * 
 * Müşteri grubu verilerinin taşınması ve dönüştürülmesi için kullanılan DTO sınıfı.
 * Müşteri grubu oluşturma, güncelleme ve görüntüleme işlemlerinde kullanılır.
 */
class CustomerGroupData
{
    /**
     * @param string $name Grup adı
     * @param string|null $description Açıklama
     * @param bool $status Durum
     * @param int|null $user_id Kullanıcı ID
     */
    public function __construct(
        public readonly string $name,
        public readonly ?string $description,
        public readonly bool $status,
        public readonly ?int $user_id,
    ) {}

    /**
     * Dizi verisinden müşteri grubu verisi oluşturur
     * 
     * @param array $data Müşteri grubu verileri dizisi
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            description: $data['description'] ?? null,
            status: $data['status'] ?? true,
            user_id: $data['user_id'],
        );
    }

    /**
     * Müşteri grubu verisini dizi formatına dönüştürür
     * 
     * @return array
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'description' => $this->description,
            'status' => $this->status,
            'user_id' => $this->user_id,
        ];
    }
} 