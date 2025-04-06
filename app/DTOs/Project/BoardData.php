<?php

declare(strict_types=1);

namespace App\DTOs\Project;

/**
 * Proje Panosu Veri Transfer Nesnesi
 * 
 * Proje panosu verilerinin taşınması ve dönüştürülmesi için kullanılan DTO sınıfı.
 * Proje panosu oluşturma ve görüntüleme işlemlerinde kullanılır.
 */
class BoardData
{
    /**
     * @param string $name Pano adı
     * @param int $project_id Proje ID
     */
    public function __construct(
        public readonly string $name,
        public readonly int $project_id,
    ) {}

    /**
     * Dizi verisinden pano verisi oluşturur
     * 
     * @param array $data Pano verileri dizisi
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            project_id: $data['project_id'],
        );
    }

    /**
     * Pano verisini dizi formatına dönüştürür
     * 
     * @return array
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'project_id' => $this->project_id,
        ];
    }
} 