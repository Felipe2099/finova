<?php

declare(strict_types=1);

namespace App\DTOs\Debt;

/**
 * Borç Veri Transfer Nesnesi
 * 
 * Borç verilerinin taşınması ve dönüştürülmesi için kullanılan DTO sınıfı.
 * Borç oluşturma, güncelleme ve görüntüleme işlemlerinde kullanılır.
 * Kıymetli madenler ve normal para birimleri için farklı hesaplama mantığı içerir.
 */
final class DebtData
{
    /**
     * @param int|null $user_id Kullanıcı ID
     * @param int|null $customer_id Müşteri ID
     * @param int|null $supplier_id Tedarikçi ID
     * @param string $type Borç türü
     * @param string|null $description Açıklama
     * @param float $amount Tutar
     * @param string $currency Para birimi (varsayılan: TRY)
     * @param float|null $buy_price Alış fiyatı
     * @param float|null $sell_price Satış fiyatı
     * @param float|null $profit_loss Kar/Zarar
     * @param string|null $due_date Vade tarihi
     * @param string $status Durum
     * @param string|null $notes Notlar
     * @param string|null $date İşlem tarihi
     */
    public function __construct(
        public readonly ?int $user_id,
        public readonly ?int $customer_id,
        public readonly ?int $supplier_id,
        public readonly string $type,
        public readonly ?string $description,
        public readonly float $amount,
        public readonly string $currency = 'TRY',
        public readonly ?float $buy_price = null,
        public readonly ?float $sell_price = null,
        public readonly ?float $profit_loss = null,
        public readonly ?string $due_date,
        public readonly string $status,
        public readonly ?string $notes,
        public readonly ?string $date = null,
    ) {}

    /**
     * Dizi verisinden borç verisi oluşturur
     * 
     * Kıymetli madenler (XAU, XAG) için gram bazında,
     * diğer para birimleri için birim bazında hesaplama yapar.
     * 
     * @param array $data Borç verileri dizisi
     * @return self
     */
    public static function fromArray(array $data): self
    {
        $currency = $data['currency'] ?? 'TRY';
        $amount = (float) $data['amount'];
        $buyPrice = isset($data['buy_price']) ? (float) $data['buy_price'] : null;

        // Kıymetli madenler için gram bazında, diğerleri için birim bazında
        if (in_array($currency, ['XAU', 'XAG'])) {
            // Gram bazında işlem
            $amount = $amount;
            $buyPrice = $buyPrice; // Gram başına fiyat
        } else {
            // Birim bazında işlem
            $amount = $amount;
            $buyPrice = $buyPrice; // Birim başına fiyat
        }

        return new self(
            user_id: isset($data['user_id']) ? (int) $data['user_id'] : auth()->id(),
            customer_id: isset($data['customer_id']) ? (int) $data['customer_id'] : null,
            supplier_id: isset($data['supplier_id']) ? (int) $data['supplier_id'] : null,
            type: $data['type'],
            description: $data['description'] ?? null,
            amount: $amount,
            currency: $currency,
            buy_price: $buyPrice,
            sell_price: null,
            profit_loss: null,
            due_date: $data['due_date'] ?? null,
            status: $data['status'] ?? 'pending',
            notes: $data['notes'] ?? null,
            date: $data['date'] ?? now()->format('Y-m-d'),
        );
    }

    /**
     * Borç verisini dizi formatına dönüştürür
     * 
     * @return array
     */
    public function toArray(): array
    {
        return [
            'user_id' => $this->user_id,
            'customer_id' => $this->customer_id,
            'supplier_id' => $this->supplier_id,
            'type' => $this->type,
            'description' => $this->description,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'buy_price' => $this->buy_price,
            'sell_price' => $this->sell_price,
            'profit_loss' => $this->profit_loss,
            'due_date' => $this->due_date,
            'status' => $this->status,
            'notes' => $this->notes,
            'date' => $this->date,
        ];
    }
}