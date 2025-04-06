<?php

declare(strict_types=1);

namespace App\DTOs\Transaction;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * İşlem Veri Transfer Nesnesi
 * 
 * Finansal işlem verilerinin taşınması ve dönüştürülmesi için kullanılan DTO sınıfı.
 * Gelir, gider, transfer, taksitli ödeme, abonelik ve kredi ödemesi gibi işlemler için kullanılır.
 * Vergi, tevkifat, taksit ve abonelik özelliklerini destekler.
 */
class TransactionData
{
    /**
     * @param int $user_id Kullanıcı ID
     * @param int|null $category_id Kategori ID
     * @param int|null $customer_id Müşteri ID
     * @param int|null $supplier_id Tedarikçi ID
     * @param int|null $source_account_id Kaynak hesap ID
     * @param int|null $destination_account_id Hedef hesap ID
     * @param string $type İşlem türü
     * @param string|null $payment_method Ödeme yöntemi
     * @param float $amount İşlem tutarı
     * @param string $date İşlem tarihi
     * @param string $currency Para birimi
     * @param float|null $exchange_rate Döviz kuru
     * @param float|null $try_equivalent TRY karşılığı
     * @param float|null $fee_amount Komisyon tutarı
     * @param string|null $description Açıklama
     * @param bool $is_subscription Abonelik mi?
     * @param string|null $subscription_period Abonelik periyodu
     * @param string|null $next_payment_date Bir sonraki ödeme tarihi
     * @param bool $auto_renew Otomatik yenileme
     * @param bool $is_taxable Vergiye tabi mi?
     * @param int|null $tax_rate Vergi oranı
     * @param float|null $tax_amount Vergi tutarı
     * @param bool $has_withholding Tevkifat var mı?
     * @param int|null $withholding_rate Tevkifat oranı
     * @param float|null $withholding_amount Tevkifat tutarı
     * @param int|null $installments Taksit sayısı
     * @param int|null $remaining_installments Kalan taksit sayısı
     * @param float|null $monthly_amount Aylık taksit tutarı
     * @param string $status İşlem durumu
     * @param int|null $reference_id Referans ID
     */
    public function __construct(
        public readonly int $user_id,
        public readonly ?int $category_id,
        public readonly ?int $customer_id,
        public readonly ?int $supplier_id,
        public readonly ?int $source_account_id,
        public readonly ?int $destination_account_id,
        public readonly string $type,
        public readonly ?string $payment_method,
        public readonly float $amount,
        public readonly string $date,
        public readonly string $currency,
        public readonly ?float $exchange_rate,
        public readonly ?float $try_equivalent,
        public readonly ?float $fee_amount,
        public readonly ?string $description,
        public readonly bool $is_subscription,
        public readonly ?string $subscription_period,
        public readonly ?string $next_payment_date,
        public readonly bool $auto_renew,
        public readonly bool $is_taxable,
        public readonly ?int $tax_rate,
        public readonly ?float $tax_amount,
        public readonly bool $has_withholding,
        public readonly ?int $withholding_rate,
        public readonly ?float $withholding_amount,
        public readonly ?int $installments,
        public readonly ?int $remaining_installments,
        public readonly ?float $monthly_amount,
        public readonly string $status = 'completed',
        public readonly ?int $reference_id = null,
    ) {}

    /**
     * Dizi verisinden işlem verisi oluşturur
     * 
     * Ödeme yöntemi için geçerli değerler: cash, bank, credit_card, crypto, virtual_pos
     * 
     * @param array $data İşlem verileri dizisi
     * @return self
     */
    public static function fromArray(array $data): self
    {
        Log::info('TransactionData Input:', $data);

        $paymentMethod = match($data['payment_method'] ?? null) {
            'cash' => 'cash',
            'bank' => 'bank',
            'credit_card' => 'credit_card',
            'crypto' => 'crypto',
            'virtual_pos' => 'virtual_pos',
            default => null
        };

        return new self(
            user_id: (int) ($data['user_id'] ?? auth()->id()),
            category_id: isset($data['category_id']) ? (int) $data['category_id'] : null,
            customer_id: isset($data['customer_id']) ? (int) $data['customer_id'] : null,
            supplier_id: isset($data['supplier_id']) ? (int) $data['supplier_id'] : null,
            source_account_id: isset($data['source_account_id']) ? (int) $data['source_account_id'] : null,
            destination_account_id: isset($data['destination_account_id']) ? (int) $data['destination_account_id'] : null,
            type: $data['type'],
            payment_method: $paymentMethod,
            amount: (float) $data['amount'],
            date: $data['date'],
            currency: $data['currency'],
            exchange_rate: isset($data['exchange_rate']) ? (float) $data['exchange_rate'] : null,
            try_equivalent: isset($data['try_equivalent']) ? (float) $data['try_equivalent'] : null,
            fee_amount: isset($data['fee_amount']) ? (float) $data['fee_amount'] : null,
            description: $data['description'] ?? null,
            is_subscription: (bool) ($data['is_subscription'] ?? false),
            subscription_period: $data['subscription_period'] ?? null,
            next_payment_date: $data['next_payment_date'] ?? null,
            auto_renew: (bool) ($data['auto_renew'] ?? false),
            is_taxable: (bool) ($data['is_taxable'] ?? false),
            tax_rate: isset($data['tax_rate']) ? (int) $data['tax_rate'] : null,
            tax_amount: isset($data['tax_amount']) ? (float) $data['tax_amount'] : null,
            has_withholding: (bool) ($data['has_withholding'] ?? false),
            withholding_rate: isset($data['withholding_rate']) ? (int) $data['withholding_rate'] : null,
            withholding_amount: isset($data['withholding_amount']) ? (float) $data['withholding_amount'] : null,
            installments: isset($data['installments']) ? (int) $data['installments'] : null,
            remaining_installments: isset($data['remaining_installments']) ? (int) $data['remaining_installments'] : null,
            monthly_amount: isset($data['monthly_amount']) ? (float) $data['monthly_amount'] : null,
            status: $data['status'] ?? 'completed',
            reference_id: isset($data['reference_id']) ? (int) $data['reference_id'] : null,
        );
    }

    /**
     * İşlem verisini dizi formatına dönüştürür
     * 
     * @return array
     */
    public function toArray(): array
    {
        return [
            'user_id' => $this->user_id,
            'category_id' => $this->category_id,
            'customer_id' => $this->customer_id,
            'supplier_id' => $this->supplier_id,
            'source_account_id' => $this->source_account_id,
            'destination_account_id' => $this->destination_account_id,
            'type' => $this->type,
            'payment_method' => $this->payment_method,
            'amount' => $this->amount,
            'date' => $this->date,
            'currency' => $this->currency,
            'exchange_rate' => $this->exchange_rate,
            'try_equivalent' => $this->try_equivalent,
            'fee_amount' => $this->fee_amount,
            'description' => $this->description,
            'is_subscription' => $this->is_subscription,
            'subscription_period' => $this->subscription_period,
            'next_payment_date' => $this->next_payment_date,
            'auto_renew' => $this->auto_renew,
            'is_taxable' => $this->is_taxable,
            'tax_rate' => $this->tax_rate,
            'tax_amount' => $this->tax_amount,
            'has_withholding' => $this->has_withholding,
            'withholding_rate' => $this->withholding_rate,
            'withholding_amount' => $this->withholding_amount,
            'installments' => $this->installments,
            'remaining_installments' => $this->remaining_installments,
            'monthly_amount' => $this->monthly_amount,
            'status' => $this->status,
            'reference_id' => $this->reference_id,
        ];
    }
}