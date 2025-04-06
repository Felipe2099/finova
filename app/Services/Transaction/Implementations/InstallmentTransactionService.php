<?php

declare(strict_types=1);

namespace App\Services\Transaction\Implementations;

use App\DTOs\Transaction\TransactionData;
use App\Models\Account;
use App\Models\Transaction;
use App\Services\Transaction\Contracts\AccountBalanceServiceInterface;
use App\Services\Transaction\Contracts\InstallmentTransactionServiceInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Taksitli ödeme işlemleri servisi
 * 
 * Kredi kartı ile yapılan taksitli ödemeleri yönetir.
 * Taksit sayısı, aylık ödeme tutarı ve ödeme tarihlerini takip eder.
 */
final class InstallmentTransactionService implements InstallmentTransactionServiceInterface
{
    public function __construct(
        private readonly AccountBalanceServiceInterface $balanceService
    ) {
    }

    /**
     * Yeni bir taksitli ödeme işlemi oluşturur
     * 
     * Kredi kartı limit kontrolü yapar ve taksit bilgilerini kaydeder.
     * 
     * @param TransactionData $data Taksitli ödeme verileri
     * @return Transaction Oluşturulan taksitli ödeme işlemi
     * @throws \InvalidArgumentException Geçersiz veriler durumunda
     */
    public function create(TransactionData $data): Transaction
    {
        return DB::transaction(function () use ($data) {
            $this->validateInstallmentData($data);
            $transaction = $this->createInstallmentRecord($data);
            $this->balanceService->updateForInstallment($transaction);
            return $transaction;
        });
    }

    /**
     * Taksit ödemesini işler
     * 
     * Kalan taksit sayısını günceller ve sonraki ödeme tarihini belirler.
     * 
     * @param Transaction $transaction İşlenecek taksitli ödeme işlemi
     */
    public function processInstallmentPayment(Transaction $transaction): void
    {
        DB::transaction(function () use ($transaction) {
            // Kalan taksit sayısını güncelle
            $transaction->remaining_installments--;
            
            // Sonraki ödeme tarihini güncelle
            if ($transaction->remaining_installments > 0) {
                $transaction->next_payment_date = Carbon::parse($transaction->next_payment_date)->addMonth();
            }
            
            $transaction->save();
        });
    }

    /**
     * Taksitli ödeme verilerini doğrular
     * 
     * Kaynak hesabın varlığını ve kredi kartı limitini kontrol eder.
     * 
     * @param TransactionData $data Doğrulanacak taksitli ödeme verileri
     * @throws \InvalidArgumentException Geçersiz veriler durumunda
     */
    private function validateInstallmentData(TransactionData $data): void
    {
        if (!$data->source_account_id) {
            throw new \InvalidArgumentException('Kaynak hesap bilgisi gereklidir.');
        }

        $account = Account::findOrFail($data->source_account_id);
        if ($account->type !== Account::TYPE_CREDIT_CARD) {
            throw new \InvalidArgumentException('Taksitli ödeme sadece kredi kartı için yapılabilir.');
        }

        if ($account->balance + $data->amount > $account->limit) {
            throw new \InvalidArgumentException('Kredi kartı limiti aşıldı.');
        }
    }

    /**
     * Taksitli ödeme kaydı oluşturur
     * 
     * @param TransactionData $data Taksitli ödeme verileri
     * @return Transaction Oluşturulan taksitli ödeme kaydı
     */
    private function createInstallmentRecord(TransactionData $data): Transaction
    {
        $description = sprintf(
            'Taksitli ödeme - %d/%d',
            $data->installment_number,
            $data->total_installments
        );

        return Transaction::create([
            ...$data->toArray(),
            'description' => $description,
        ]);
    }
} 