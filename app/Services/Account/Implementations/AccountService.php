<?php

declare(strict_types=1);

namespace App\Services\Account\Implementations;

use App\Models\Account;
use App\Models\Transaction;
use App\DTOs\Account\AccountData;
use App\Services\Account\Contracts\AccountServiceInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Enums\PaymentMethodEnum;
use Filament\Notifications\Notification;

/**
 * Hesap servisi implementasyonu
 * 
 * Hesap işlemlerinin yönetimi için gerekli metodları içerir.
 * Hesapların oluşturulması, güncellenmesi, silinmesi ve diğer hesap işlemlerini gerçekleştirir.
 */
class AccountService implements AccountServiceInterface
{
    /**
     * Yeni bir hesap oluşturur
     * 
     * @param AccountData $data Hesap verileri
     * @return Account Oluşturulan hesap
     */
    public function createAccount(AccountData $data): Account
    {
        return DB::transaction(function () use ($data) {
            $account = new Account();
            $account->user_id = $data->user_id ?? auth()->id();
            $account->name = $data->name;
            $account->type = $data->type;
            $account->currency = $data->currency;
            $account->balance = $data->balance ?? 0;
            $account->details = $this->prepareDetails($data);
            $account->status = $data->status ?? true;
            $account->save();

            return $account;
        });
    }

    /**
     * Mevcut bir hesabı günceller
     * 
     * @param Account $account Güncellenecek hesap
     * @param AccountData $data Yeni hesap verileri
     * @return Account Güncellenmiş hesap
     */
    public function updateAccount(Account $account, AccountData $data): Account
    {
        return DB::transaction(function () use ($account, $data) {
            $account->name = $data->name;
            $account->currency = $data->currency;
            $account->balance = $data->balance ?? $account->balance;
            $account->details = $this->prepareDetails($data);
            $account->status = $data->status ?? $account->status;
            $account->save();

            return $account;
        });
    }

    /**
     * Hesabı siler
     * 
     * @param Account $account Silinecek hesap
     * @return bool İşlem başarılı ise true, değilse false
     */
    public function delete(Account $account): bool
    {
        try {
            // İşlem kontrolü
            if ($account->sourceTransactions()->withTrashed()->exists() || $account->destinationTransactions()->withTrashed()->exists()) {
                $accountType = match($account->type) {
                    Account::TYPE_CRYPTO_WALLET => 'kripto cüzdan',
                    Account::TYPE_VIRTUAL_POS => 'sanal POS',
                    Account::TYPE_BANK_ACCOUNT => 'banka hesabı',
                    Account::TYPE_CREDIT_CARD => 'kredi kartı',
                    default => 'hesap'
                };

                throw new \Exception(
                    "Bu {$accountType} işlem kayıtları bulunduğu için silinemez, pasife alabilirsiniz. "
                );
            }

            return $account->delete();
        } catch (\Exception $e) {
            Notification::make()
                ->danger()
                ->title('Hesap Silinemez!')
                ->body($e->getMessage())
                ->duration(10000)
                ->send();
            
            return false;
        }
    }

    /**
     * Hesap detaylarını hazırlar
     * 
     * Hesap tipine göre gerekli detayları oluşturur.
     * 
     * @param AccountData $data Hesap verileri
     * @return array Hazırlanmış hesap detayları
     */
    private function prepareDetails(AccountData $data): array
    {
        $details = $data->details ?? [];
        return match ($data->type) {
            Account::TYPE_BANK_ACCOUNT => [
                'bank_name' => $details['bank_name'] ?? null,
                'account_number' => $details['account_number'] ?? null,
                'iban' => $details['iban'] ?? null,
                'branch_code' => $details['branch_code'] ?? null,
                'branch_name' => $details['branch_name'] ?? null,
            ],
            Account::TYPE_CREDIT_CARD => [
                'bank_name' => $details['bank_name'] ?? null,
                'credit_limit' => $details['credit_limit'] ?? null,
                'statement_day' => $details['statement_day'] ?? null,
                'current_debt' => $details['current_debt'] ?? 0,
            ],
            Account::TYPE_CRYPTO_WALLET => [
                'platform' => $details['platform'] ?? null,
                'wallet_address' => $details['wallet_address'] ?? null,
            ],
            Account::TYPE_VIRTUAL_POS => [
                'provider' => $details['provider'] ?? null
            ],
            default => [],
        };
    }

    /**
     * Taksitli alışveriş işlemi oluşturur
     * 
     * @param AccountData $data Taksitli alışveriş verileri
     * @return Transaction Oluşturulan taksitli alışveriş işlemi
     */
    public function createInstallmentPurchase(AccountData $data): Transaction
    {
        return DB::transaction(function () use ($data) {
            $account = Account::where('type', Account::TYPE_CREDIT_CARD)
                ->where('id', $data->account_id)
                ->firstOrFail();

            $details = $account->details;
            $availableLimit = ($details['credit_limit'] ?? 0) - $account->balance;
            if ($data->amount > $availableLimit) {
                throw new \Exception('Kredi kartında yeterli limit bulunmuyor.');
            }

            $transaction = new Transaction();
            $transaction->user_id = auth()->id();
            $transaction->source_account_id = $account->id;
            $transaction->type = Transaction::TYPE_EXPENSE;
            $transaction->amount = $data->amount;
            $transaction->currency = $account->currency;
            $transaction->exchange_rate = $data->exchange_rate ?? null;
            $transaction->try_equivalent = $data->amount * ($data->exchange_rate ?? 1);
            $transaction->date = $data->transaction_date;
            $transaction->description = $data->description;
            $transaction->category_id = $data->category_id;
            $transaction->supplier_id = $data->supplier_id ?? null;
            $transaction->installments = $data->installments ?? 1;
            $transaction->remaining_installments = $data->installments ?? 1;
            $transaction->monthly_amount = $data->amount / ($data->installments ?? 1);
            $transaction->next_payment_date = $data->next_payment_date ?? null;
            $transaction->save();

            $account->balance += $data->amount;
            $account->save();

            return $transaction;
        });
    }

    /**
     * Kredi kartı için taksitli ödemeleri getirir
     * 
     * @param int $accountId Kredi kartı hesap ID'si
     * @return \Illuminate\Database\Eloquent\Collection Taksitli ödemeler
     */
    public function getInstallmentsForCard(int $accountId): \Illuminate\Database\Eloquent\Collection
    {
        return Transaction::where('source_account_id', $accountId)
            ->where('type', Transaction::TYPE_EXPENSE)
            ->where('installments', '>', 1)
            ->where('remaining_installments', '>', 0)
            ->orderBy('date')
            ->get();
    }

    /**
     * Hesap bakiyesini günceller
     * 
     * @param int $accountId Hesap ID'si
     * @param float $amount İşlem miktarı
     * @param string $currency Para birimi
     * @param string $operation İşlem tipi (add/subtract)
     */
    public function updateAccountBalance(int $accountId, float $amount, string $currency, string $operation): void
    {
        $account = Account::findOrFail($accountId);
        DB::transaction(function () use ($account, $amount, $currency, $operation) {
            $adjustedAmount = $amount;
            if ($account->currency !== $currency) {
                $adjustedAmount = $amount * ($account->exchange_rate ?? 1);
            }

            switch ($account->type) {
                case Account::TYPE_CREDIT_CARD:
                    // Kredi kartı: Harcama -> borç artar, Ödeme -> borç azalır
                    if ($operation === 'add') {
                        $account->balance -= $adjustedAmount;
                    } else {
                        $account->balance += $adjustedAmount;
                    }
                    break;

                case Account::TYPE_BANK_ACCOUNT:
                case Account::TYPE_CRYPTO_WALLET:
                case Account::TYPE_VIRTUAL_POS:
                case Account::TYPE_CASH:
                    // Normal hesaplar: Gelir -> bakiye artar, Gider -> bakiye azalır
                    if ($operation === 'add') {
                        $account->balance += $adjustedAmount;
                    } else {
                        $account->balance -= $adjustedAmount;
                    }
                    break;
            }

            $account->save();
        });
    }

    /**
     * Kripto cüzdan hesabı oluşturur
     * 
     * @param AccountData $data Kripto cüzdan verileri
     * @return Account Oluşturulan kripto cüzdan hesabı
     */
    public function createCryptoWallet(AccountData $data): Account
    {


        return DB::transaction(function () use ($data) {
            $account = new Account();
            
            // user_id'yi direkt set edelim
            $account->user_id = auth()->id() ?? $data->user_id;
            
            if (!$account->user_id) {
                throw new \Exception('User ID is required!');
            }

            $account->name = $data->name;
            $account->type = Account::TYPE_CRYPTO_WALLET;
            $account->currency = $data->currency;
            $account->balance = $data->balance ?? 0;
            $account->details = [
                'platform' => $data->details['platform'] ?? null,
                'wallet_address' => $data->details['wallet_address'] ?? null,
            ];
            $account->status = $data->status ?? true;

            Log::info('Before Save:', [
                'account_data' => $account->toArray()
            ]);

            $account->save();
            return $account;
        });
    }

    /**
     * Sanal POS hesabı oluşturur
     * 
     * @param AccountData $data Sanal POS verileri
     * @return Account Oluşturulan sanal POS hesabı
     */
    public function createVirtualPos(AccountData $data): Account
    {
        return DB::transaction(function () use ($data) {
            $account = new Account();
            $account->user_id = $data->user_id ?? auth()->id();
            $account->name = $data->name;
            $account->type = Account::TYPE_VIRTUAL_POS;
            $account->currency = $data->currency;
            $account->balance = $data->balance ?? 0;
            $account->details = [
                'provider' => $data->details['provider'] ?? null,
                'merchant_id' => $data->details['merchant_id'] ?? null,
                'terminal_id' => $data->details['terminal_id'] ?? null,
            ];
            $account->status = $data->status ?? true;
            $account->save();

            return $account;
        });
    }

    /**
     * Kredi kartı ödemesi yapar
     * 
     * @param int $creditCardId Kredi kartı hesap ID'si
     * @param float $amount Ödeme miktarı
     * @param string $paymentMethod Ödeme yöntemi
     * @param int|null $sourceAccountId Kaynak hesap ID'si
     * @param string|null $date İşlem tarihi
     */
    public function makeCardPayment(
        int $creditCardId, 
        float $amount, 
        string $paymentMethod,
        ?int $sourceAccountId = null,
        ?string $date = null
    ): void {
        try {
            DB::transaction(function () use ($creditCardId, $amount, $paymentMethod, $sourceAccountId, $date) {
                $creditCard = Account::findOrFail($creditCardId);
                
                // Mevcut borç miktarını al
                $currentDebt = $creditCard->balance;
                
                // Ödeme miktarı borçtan fazla ise
                if ($amount > $currentDebt) {
                    // Fazla ödeme miktarını hesapla
                    $overpayment = $amount - $currentDebt;
                    
                    // Kullanıcıya bildirim gönder
                    Notification::make()
                        ->warning()
                        ->title('Fazla Ödeme!')
                        ->body("Kredi kartı borcunuzdan {$overpayment} {$creditCard->currency} daha fazla ödeme yaptınız. Ödeme miktarı mevcut borç kadar düşürüldü.")
                        ->duration(8000)
                        ->send();
                    
                    // Ödeme miktarını mevcut borç kadar sınırla
                    $amount = $currentDebt;
                }
                
                // Kredi kartı borcunu azalt (0'dan küçük olamaz)
                $creditCard->balance = max(0, $creditCard->balance - $amount);
                $creditCard->save();

                // Eğer banka hesabından ödeme yapıldıysa
                if ($paymentMethod === PaymentMethodEnum::BANK->value && $sourceAccountId) {
                    $sourceAccount = Account::findOrFail($sourceAccountId);
                    $sourceAccount->balance -= $amount;
                    $sourceAccount->save();
                }

                // Ödeme kaydını oluştur
                Transaction::create([
                    'user_id' => auth()->id(),
                    'type' => 'payment', // Borç hesaplamalarında 'payment' türü kullanılıyor
                    'source_account_id' => $sourceAccountId,
                    'destination_account_id' => $creditCardId,
                    'amount' => $amount,
                    'currency' => $creditCard->currency,
                    'try_equivalent' => $amount, // TL eşdeğeri
                    'payment_method' => $paymentMethod,
                    'date' => $date ?? now(),
                    'description' => "{$creditCard->name} Ödemesi",
                    'status' => 'completed'
                ]);

                Notification::make()
                    ->success()
                    ->title('Ödeme Başarılı!')
                    ->body("{$creditCard->name} kartına {$amount} {$creditCard->currency} tutarında ödeme yapıldı.")
                    ->duration(5000)
                    ->send();
            });
        } catch (\Exception $e) {
            Notification::make()
                ->danger()
                ->title('Ödeme Başarısız!')
                ->body('Kredi kartı ödemesi sırasında bir hata oluştu: ' . $e->getMessage())
                ->duration(10000)
                ->send();

            throw $e;
        }
    }
}