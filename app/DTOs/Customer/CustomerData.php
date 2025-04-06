<?php

declare(strict_types=1);

namespace App\DTOs\Customer;

/**
 * Müşteri Veri Transfer Nesnesi
 * 
 * Müşteri verilerinin taşınması ve dönüştürülmesi için kullanılan DTO sınıfı.
 * Müşteri oluşturma, güncelleme ve görüntüleme işlemlerinde kullanılır.
 */
class CustomerData
{
    /**
     * @param string $name Müşteri adı
     * @param string $type Müşteri türü
     * @param string|null $email E-posta adresi
     * @param string|null $phone Telefon numarası
     * @param string|null $tax_number Vergi numarası
     * @param string|null $tax_office Vergi dairesi
     * @param string|null $city Şehir
     * @param string|null $district İlçe
     * @param string|null $address Adres
     * @param string|null $description Açıklama
     * @param bool $status Durum
     * @param int|null $customer_group_id Müşteri grubu ID
     * @param int|null $user_id Kullanıcı ID
     */
    public function __construct(
        public readonly string $name,
        public readonly string $type,
        public readonly ?string $email,
        public readonly ?string $phone,
        public readonly ?string $tax_number,
        public readonly ?string $tax_office,
        public readonly ?string $city,
        public readonly ?string $district,
        public readonly ?string $address,
        public readonly ?string $description,
        public readonly bool $status,
        public readonly ?int $customer_group_id,
        public readonly ?int $user_id = null,
    ) {}

    /**
     * Dizi verisinden müşteri verisi oluşturur
     * 
     * @param array $data Müşteri verileri dizisi
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            type: $data['type'],
            email: $data['email'] ?? null,
            phone: $data['phone'] ?? null,
            tax_number: $data['tax_number'] ?? null,
            tax_office: $data['tax_office'] ?? null,
            city: $data['city'] ?? null,
            district: $data['district'] ?? null,
            address: $data['address'] ?? null,
            description: $data['description'] ?? null,
            status: $data['status'] ?? true,
            customer_group_id: isset($data['customer_group_id']) ? (int) $data['customer_group_id'] : null,
            user_id: $data['user_id'] ?? null,
        );
    }

    /**
     * Müşteri verisini dizi formatına dönüştürür
     * 
     * @return array
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'type' => $this->type,
            'email' => $this->email,
            'phone' => $this->phone,
            'tax_number' => $this->tax_number,
            'tax_office' => $this->tax_office,
            'city' => $this->city,
            'district' => $this->district,
            'address' => $this->address,
            'description' => $this->description,
            'status' => $this->status,
            'customer_group_id' => $this->customer_group_id,
            'user_id' => $this->user_id,
        ];
    }
} 