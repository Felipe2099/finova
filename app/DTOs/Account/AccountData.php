<?php

declare(strict_types=1);

namespace App\DTOs\Account;

use App\Models\Account;
use Illuminate\Http\Request;

/**
 * Hesap Veri Transfer Nesnesi
 * 
 * Hesap verilerinin taşınması ve dönüştürülmesi için kullanılan DTO sınıfı.
 * Hesap oluşturma, güncelleme ve görüntüleme işlemlerinde kullanılır.
 */
final class AccountData
{
    /**
     * @param string $name Hesap adı
     * @param string $type Hesap türü
     * @param string $currency Para birimi
     * @param float|null $balance Bakiye
     * @param string|null $description Açıklama
     * @param bool|null $status Durum
     * @param int|null $user_id Kullanıcı ID
     * @param int|null $id Hesap ID
     * @param array|null $details Detaylar
     */
    public function __construct(
        public readonly string $name,
        public readonly string $type,
        public readonly string $currency,
        public readonly ?float $balance = 0,
        public readonly ?string $description = null,
        public readonly ?bool $status = true,
        public readonly ?int $user_id = null,
        public readonly ?int $id = null,
        public readonly ?array $details = null,
    ) {}

    /**
     * HTTP isteğinden hesap verisi oluşturur
     * 
     * @param Request $request HTTP isteği
     * @return self
     */
    public static function fromRequest(Request $request): self
    {
        return new self(
            user_id: $request->input('user_id'),
            name: $request->string('name')->toString(),
            type: $request->string('type')->toString(),
            currency: $request->string('currency')->toString(),
            balance: $request->input('balance') ? (float) $request->input('balance') : null,
            status: $request->boolean('status'),
            details: $request->input('details'),
            description: $request->input('description'),
        );
    }

    /**
     * Model nesnesinden hesap verisi oluşturur
     * 
     * @param Account $account Hesap modeli
     * @return self
     */
    public static function fromModel(Account $account): self
    {
        return new self(
            user_id: $account->user_id,
            name: $account->name,
            type: $account->type,
            currency: $account->currency,
            balance: $account->balance,
            status: $account->status,
            details: $account->details,
            description: $account->description,
            id: $account->id,
        );
    }

    /**
     * Dizi verisinden hesap verisi oluşturur
     * 
     * @param array $data Hesap verileri dizisi
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            user_id: $data['user_id'] ?? null,
            name: $data['name'],
            type: $data['type'],
            currency: $data['currency'],
            balance: isset($data['balance']) ? (float) $data['balance'] : null,
            status: isset($data['status']) ? (bool) $data['status'] : null,
            details: $data['details'] ?? null,
            description: $data['description'] ?? null,
            id: $data['id'] ?? null,
        );
    }

    /**
     * Hesap verisini dizi formatına dönüştürür
     * 
     * @return array
     */
    public function toArray(): array
    {
        return [
            'user_id' => $this->user_id,
            'name' => $this->name,
            'type' => $this->type,
            'currency' => $this->currency,
            'balance' => $this->balance,
            'status' => $this->status,
            'details' => $this->details,
            'description' => $this->description,
            'id' => $this->id,
        ];
    }
} 