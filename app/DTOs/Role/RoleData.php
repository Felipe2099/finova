<?php

declare(strict_types=1);

namespace App\DTOs\Role;

/**
 * Rol Veri Transfer Nesnesi
 * 
 * Rol verilerinin taşınması ve dönüştürülmesi için kullanılan DTO sınıfı.
 * Rol oluşturma, güncelleme ve görüntüleme işlemlerinde kullanılır.
 * İzin yönetimi için gerekli bilgileri içerir.
 */
class RoleData
{
    /**
     * @param string $name Rol adı
     * @param array|null $permissions İzinler dizisi
     */
    public function __construct(
        public readonly string $name,
        public readonly ?array $permissions = [],
    ) {}

    /**
     * Dizi verisinden rol verisi oluşturur
     * 
     * @param array $data Rol verileri dizisi
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            permissions: $data['permissions'] ?? [],
        );
    }

    /**
     * Rol verisini dizi formatına dönüştürür
     * 
     * @return array
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'permissions' => $this->permissions,
        ];
    }
}