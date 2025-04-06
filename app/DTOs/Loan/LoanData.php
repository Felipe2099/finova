<?php

declare(strict_types=1);

namespace App\DTOs\Loan;

/**
 * Kredi Veri Transfer Nesnesi
 * 
 * Kredi verilerinin taşınması ve dönüştürülmesi için kullanılan DTO sınıfı.
 * Kredi oluşturma, güncelleme ve görüntüleme işlemlerinde kullanılır.
 * Taksitli ödemeler ve kredi takibi için gerekli bilgileri içerir.
 */
final class LoanData
{
    /**
     * @param int|null $user_id Kullanıcı ID
     * @param string $bank_name Banka adı
     * @param string $loan_type Kredi türü
     * @param float $amount Kredi tutarı
     * @param float $monthly_payment Aylık taksit tutarı
     * @param int $installments Toplam taksit sayısı
     * @param int $remaining_installments Kalan taksit sayısı
     * @param string $start_date Başlangıç tarihi
     * @param string $next_payment_date Bir sonraki ödeme tarihi
     * @param string|null $due_date Vade tarihi
     * @param float $remaining_amount Kalan kredi tutarı
     * @param string $status Kredi durumu
     * @param string|null $notes Notlar
     */
    public function __construct(
        public readonly ?int $user_id,
        public readonly string $bank_name,
        public readonly string $loan_type,
        public readonly float $amount,
        public readonly float $monthly_payment,
        public readonly int $installments,
        public readonly int $remaining_installments,
        public readonly string $start_date,
        public readonly string $next_payment_date,
        public readonly ?string $due_date,
        public readonly float $remaining_amount,
        public readonly string $status,
        public readonly ?string $notes,
    ) {}

    /**
     * Dizi verisinden kredi verisi oluşturur
     * 
     * @param array $data Kredi verileri dizisi
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            user_id: $data['user_id'] ?? auth()->id(),
            bank_name: $data['bank_name'],
            loan_type: $data['loan_type'],
            amount: (float) $data['amount'],
            monthly_payment: (float) $data['monthly_payment'],
            installments: (int) $data['installments'],
            remaining_installments: (int) ($data['remaining_installments'] ?? $data['installments']),
            start_date: $data['start_date'],
            next_payment_date: $data['next_payment_date'] ?? $data['start_date'],
            due_date: $data['due_date'] ?? null,
            remaining_amount: (float) ($data['remaining_amount'] ?? $data['amount']),
            status: $data['status'] ?? 'pending',
            notes: $data['notes'] ?? null,
        );
    }

    /**
     * Kredi verisini dizi formatına dönüştürür
     * 
     * @return array
     */
    public function toArray(): array
    {
        return [
            'user_id' => $this->user_id,
            'bank_name' => $this->bank_name,
            'loan_type' => $this->loan_type,
            'amount' => $this->amount,
            'monthly_payment' => $this->monthly_payment,
            'installments' => $this->installments,
            'remaining_installments' => $this->remaining_installments,
            'start_date' => $this->start_date,
            'next_payment_date' => $this->next_payment_date,
            'due_date' => $this->due_date,
            'remaining_amount' => $this->remaining_amount,
            'status' => $this->status,
            'notes' => $this->notes,
        ];
    }
}