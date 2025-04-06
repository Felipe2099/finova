<?php

declare(strict_types=1);

namespace App\DTOs\User;

/**
 * Kullanıcı Veri Transfer Nesnesi
 * 
 * Kullanıcı verilerinin taşınması ve dönüştürülmesi için kullanılan DTO sınıfı.
 * Kullanıcı oluşturma, güncelleme ve görüntüleme işlemlerinde kullanılır.
 */
class UserData
{
    /**
     * @param string $name Kullanıcı adı
     * @param string $email E-posta adresi
     * @param string|null $phone Telefon numarası
     * @param string|null $password Şifre (yeni oluşturma veya güncelleme için)
     * @param bool $status Durum
     * @param bool $has_commission Komisyon hakkı var mı
     * @param float|null $commission_rate Komisyon oranı
     * @param array|null $roles Roller dizisi
     */
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly ?string $phone = null,
        public readonly ?string $password = null,
        public readonly bool $status = true,
        public readonly bool $has_commission = false,
        public readonly ?float $commission_rate = null,
        public readonly ?array $roles = null,
    ) {}

    /**
     * Dizi verisinden kullanıcı verisi oluşturur
     * 
     * @param array $data Kullanıcı verileri dizisi
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            email: $data['email'],
            phone: $data['phone'] ?? null,
            password: $data['password'] ?? null,
            status: $data['status'] ?? true,
            has_commission: $data['has_commission'] ?? false,
            commission_rate: isset($data['commission_rate']) ? (float) $data['commission_rate'] : null,
            roles: $data['roles'] ?? null,
        );
    }

    /**
     * Kullanıcı verisini dizi formatına dönüştürür
     * 
     * @return array
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'password' => $this->password,
            'status' => $this->status,
            'has_commission' => $this->has_commission,
            'commission_rate' => $this->commission_rate,
            'roles' => $this->roles,
        ];
    }

    /**
     * Kullanıcının Model oluşturma/güncelleme için kullanılacak verileri döndürür
     * Şifre null ise dışarıda bırakılır (güncelleme durumunda)
     * 
     * @return array
     */
    public function toModelData(): array
    {
        $data = [
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'status' => $this->status,
            'has_commission' => $this->has_commission,
            'commission_rate' => $this->commission_rate,
        ];

        if ($this->password) {
            $data['password'] = $this->password;
        }

        return $data;
    }
} 