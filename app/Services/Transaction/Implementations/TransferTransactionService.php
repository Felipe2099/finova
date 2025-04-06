<?php

declare(strict_types=1);

namespace App\Services\Transaction\Implementations;

use App\DTOs\Transaction\TransactionData;
use App\Models\Account;
use App\Models\Transaction;
use App\Services\Transaction\Contracts\AccountBalanceServiceInterface;
use App\Services\Transaction\Contracts\TransferTransactionServiceInterface;
use Illuminate\Support\Facades\DB;

/**
 * Transfer işlemleri servisi
 * 
 * Hesaplar arası para transferi işlemlerini yönetir.
 * Farklı para birimleri arasında transfer yapılabilir.
 * Transfer sırasında kur dönüşümü yapılır.
 */
final class TransferTransactionService implements TransferTransactionServiceInterface
{
    public function __construct(
        private readonly AccountBalanceServiceInterface $balanceService
    ) {
    }

    /**
     * Yeni bir transfer işlemi oluşturur
     * 
     * Kaynak ve hedef hesaplar arasında para transferi yapar.
     * Farklı para birimleri arasında transfer yapılabilir.
     * 
     * @param TransactionData $data Transfer verileri
     * @return Transaction Oluşturulan transfer işlemi
     * @throws \InvalidArgumentException Geçersiz transfer verileri durumunda
     */
    public function create(TransactionData $data): Transaction
    {
        return DB::transaction(function () use ($data) {
            $this->validateTransferData($data);
            $rates = $this->calculateExchangeRates($data);
            $transaction = $this->createTransferRecord($data, $rates);
            $this->balanceService->updateForTransfer($transaction, $rates);
            return $transaction;
        });
    }

    /**
     * Kur hesaplamalarını yapar
     * 
     * Kaynak ve hedef para birimleri farklıysa kur dönüşümü yapar.
     * 
     * @param TransactionData $data Transfer verileri
     * @return array Kur bilgileri
     */
    private function calculateExchangeRates(TransactionData $data): array
    {
        if ($data->source_currency === $data->destination_currency) {
            return [];
        }

        // TODO: Implement exchange rate calculation
        return [
            $data->source_currency => [
                $data->destination_currency => 1.0
            ]
        ];
    }

    /**
     * Transfer kaydı oluşturur
     * 
     * @param TransactionData $data Transfer verileri
     * @param array $rates Kur bilgileri
     * @return Transaction Oluşturulan transfer kaydı
     */
    private function createTransferRecord(TransactionData $data, array $rates): Transaction
    {
        $description = $this->generateTransferDescription($data);
        
        return Transaction::create([
            ...$data->toArray(),
            'description' => $description,
            'exchange_rates' => $rates,
        ]);
    }

    /**
     * Transfer açıklaması oluşturur
     * 
     * @param TransactionData $data Transfer verileri
     * @return string Oluşturulan açıklama
     */
    private function generateTransferDescription(TransactionData $data): string
    {
        $sourceAccount = Account::findOrFail($data->source_account_id);
        $targetAccount = Account::findOrFail($data->destination_account_id);

        return sprintf(
            '%s hesabından %s hesabına transfer',
            $sourceAccount->name,
            $targetAccount->name
        );
    }

    /**
     * Transfer verilerini doğrular
     * 
     * Kaynak ve hedef hesapların varlığını ve yeterli bakiyeyi kontrol eder.
     * 
     * @param TransactionData $data Doğrulanacak transfer verileri
     * @throws \InvalidArgumentException Geçersiz veriler durumunda
     */
    private function validateTransferData(TransactionData $data): void
    {
        if (!$data->source_account_id || !$data->destination_account_id) {
            throw new \InvalidArgumentException('Kaynak ve hedef hesap bilgileri gereklidir.');
        }

        if ($data->source_account_id === $data->destination_account_id) {
            throw new \InvalidArgumentException('Kaynak ve hedef hesap aynı olamaz.');
        }

        $sourceAccount = Account::findOrFail($data->source_account_id);
        if ($sourceAccount->balance < $data->amount) {
            throw new \InvalidArgumentException('Yetersiz bakiye.');
        }
    }
} 