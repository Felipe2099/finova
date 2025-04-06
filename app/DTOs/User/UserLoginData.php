<?php

declare(strict_types=1);

namespace App\DTOs\User;

/**
 * Kullanıcı Giriş Veri Transfer Nesnesi
 * 
 * Kullanıcı giriş işlemleri sırasında veri taşımak için kullanılır.
 */
class UserLoginData
{
    /**
     * @param string $email E-posta adresi
     * @param string $password Şifre
     * @param bool $remember_me Beni hatırla seçeneği
     */
    public function __construct(
        public readonly string $email,
        public readonly string $password,
        public readonly bool $remember_me = false,
    ) {}

    /**
     * Dizi verisinden login verisi oluşturur
     * 
     * @param array $data Login verileri dizisi
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            email: $data['email'],
            password: $data['password'],
            remember_me: $data['remember_me'] ?? false,
        );
    }

    /**
     * Login verisini dizi formatına dönüştürür
     * 
     * @return array
     */
    public function toArray(): array
    {
        return [
            'email' => $this->email,
            'password' => $this->password,
            'remember_me' => $this->remember_me,
        ];
    }

    /**
     * Kimlik doğrulama için gerekli alanları döndürür
     * 
     * @return array
     */
    public function credentials(): array
    {
        return [
            'email' => $this->email,
            'password' => $this->password,
        ];
    }
} 