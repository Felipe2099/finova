<?php

declare(strict_types=1);

namespace App\DTOs\Supplier;

/**
 * Tedarikçi Veri Transfer Nesnesi
 * 
 * Tedarikçi verilerinin taşınması ve dönüştürülmesi için kullanılan DTO sınıfı.
 * Tedarikçi oluşturma, güncelleme ve görüntüleme işlemlerinde kullanılır.
 */
class SupplierData
{
    /**
     * @param string $name Tedarikçi adı
     * @param string|null $contact_name İletişim kişisi
     * @param string|null $phone Telefon numarası
     * @param string|null $email E-posta adresi
     * @param string|null $address Adres
     * @param string|null $notes Notlar
     * @param bool $status Durum
     */
    public function __construct(
        public readonly string $name,
        public readonly ?string $contact_name,
        public readonly ?string $phone,
        public readonly ?string $email,
        public readonly ?string $address,
        public readonly ?string $notes,
        public readonly bool $status,
    ) {}

    /**
     * Dizi verisinden tedarikçi verisi oluşturur
     * 
     * @param array $data Tedarikçi verileri dizisi
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            contact_name: $data['contact_name'] ?? null,
            phone: $data['phone'] ?? null,
            email: $data['email'] ?? null,
            address: $data['address'] ?? null,
            notes: $data['notes'] ?? null,
            status: $data['status'] ?? true,
        );
    }

    /**
     * Tedarikçi verisini dizi formatına dönüştürür
     * 
     * @return array
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'contact_name' => $this->contact_name,
            'phone' => $this->phone,
            'email' => $this->email,
            'address' => $this->address,
            'notes' => $this->notes,
            'status' => $this->status,
        ];
    }
}