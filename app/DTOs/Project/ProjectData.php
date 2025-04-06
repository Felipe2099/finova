<?php

declare(strict_types=1);

namespace App\DTOs\Project;

/**
 * Proje Veri Transfer Nesnesi
 * 
 * Proje verilerinin taşınması ve dönüştürülmesi için kullanılan DTO sınıfı.
 * Proje oluşturma, güncelleme ve görüntüleme işlemlerinde kullanılır.
 */
class ProjectData
{
    /**
     * @param string $name Proje adı
     * @param string|null $description Proje açıklaması
     * @param string $status Proje durumu
     * @param int|null $created_by Oluşturan kullanıcı ID
     */
    public function __construct(
        public readonly string $name,
        public readonly ?string $description,
        public readonly string $status,
        public readonly ?int $created_by = null,
    ) {}

    /**
     * Dizi verisinden proje verisi oluşturur
     * 
     * @param array $data Proje verileri dizisi
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            description: $data['description'] ?? null,
            status: $data['status'],
            created_by: $data['created_by'] ?? null,
        );
    }

    /**
     * Proje verisini dizi formatına dönüştürür
     * 
     * @return array
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'description' => $this->description,
            'status' => $this->status,
            'created_by' => $this->created_by,
        ];
    }
} 