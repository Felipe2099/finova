<?php

declare(strict_types=1);

namespace App\Services\Transaction\Implementations;

use App\Models\Account;
use App\Models\Transaction;
use App\Services\Transaction\Contracts\AccountBalanceServiceInterface;
use App\Services\Currency\CurrencyConversionService;
use App\Enums\TransactionTypeEnum;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Hesap bakiyesi servisi
 * 
 * İşlemler sonrası hesap bakiyelerini günceller.
 * Gelir, gider, transfer ve taksitli ödeme işlemleri için bakiye güncellemelerini yapar.
 */
final class AccountBalanceService implements AccountBalanceServiceInterface
{
    private CurrencyConversionService $currencyService;

    public function __construct(CurrencyConversionService $currencyService)
    {
        $this->currencyService = $currencyService;
    }

    /**
     * Gelir işlemi için hesap bakiyesini günceller
     * 
     * Hedef hesabın bakiyesini artırır.
     * 
     * @param Transaction $transaction İşlenecek gelir işlemi
     */
    public function updateForIncome(Transaction $transaction): void
    {
        if ($transaction->destinationAccount) {
            $this->updateBalance(
                $transaction->destinationAccount,
                (float) $transaction->amount,
                $transaction->currency,
                Carbon::parse($transaction->date)
            );
        }
    }

    /**
     * Gider işlemi için hesap bakiyesini günceller
     * 
     * Kaynak hesabın bakiyesini azaltır.
     * Kredi kartı işlemleri için özel mantık uygular.
     * 
     * @param Transaction $transaction İşlenecek gider işlemi
     */
    public function updateForExpense(Transaction $transaction): void
    {
        if ($transaction->sourceAccount) {
            $amount = 0.0;
            // Kredi kartı ile ödeme yapılıyorsa borç artar
            if ($transaction->sourceAccount->type === Account::TYPE_CREDIT_CARD) {
                $amount = (float) $transaction->amount; // Borç artar (pozitif işaret)
            } else {
                // Normal hesap ile ödeme yapılıyorsa bakiye azalır
                $amount = -(float) $transaction->amount; // Bakiye azalır (eksi işaret)
            }

            if ($amount != 0) {
                $this->updateBalance(
                    $transaction->sourceAccount,
                    $amount,
                    $transaction->currency,
                    Carbon::parse($transaction->date)
                );
            }
        }
    }

    /**
     * Transfer işlemi için hesap bakiyelerini günceller
     * 
     * Kaynak hesabın bakiyesini azaltır ve hedef hesabın bakiyesini artırır.
     * Farklı para birimleri arasında transfer yapılabilir.
     * 
     * @param Transaction $transaction İşlenecek transfer işlemi
     */
    public function updateForTransfer(Transaction $transaction): void
    {
        if ($transaction->sourceAccount) {
            $this->updateBalance(
                $transaction->sourceAccount,
                -$transaction->amount,
                $transaction->currency,
                Carbon::parse($transaction->date)
            );
        }

        if ($transaction->destinationAccount) {
            $this->updateBalance(
                $transaction->destinationAccount,
                $transaction->amount,
                $transaction->currency,
                Carbon::parse($transaction->date)
            );
        }
    }

    /**
     * Taksitli ödeme işlemi için hesap bakiyesini günceller
     * 
     * Hesap bakiyesini günceller (Mevcut implementasyon bakiyeyi azaltıyor).
     * 
     * @param Transaction $transaction İşlenecek taksitli ödeme işlemi
     */
    public function updateForInstallment(Transaction $transaction): void
    {
        if ($transaction->sourceAccount) {
             // Mevcut implementasyona göre bakiye azaltılır.
            $this->updateBalance(
                $transaction->sourceAccount,
                -(float) $transaction->amount, 
                $transaction->currency,
                Carbon::parse($transaction->date)
            );
        }
    }

    /**
     * Kredi Ödemesi işlemi için hesap bakiyesini günceller
     * Kredi kartı ile ödeme yapılıyorsa borç artar.
     */
    public function updateForLoanPayment(Transaction $transaction): void
    {
        if ($transaction->sourceAccount) {
            $amount = 0.0;
            // Kredi kartı ise BORÇ ARTAR (+ amount)
            if ($transaction->sourceAccount->type === Account::TYPE_CREDIT_CARD) {
                $amount = (float) $transaction->amount;
            } else {
                // Normal hesap ise BAKİYE AZALIR (- amount)
                $amount = -(float) $transaction->amount;
            }
            if ($amount != 0) {
                $this->updateBalance(
                    $transaction->sourceAccount,
                    $amount,
                    $transaction->currency,
                    Carbon::parse($transaction->date)
                );
            }
        }
    }

    /**
     * İşlemi geri alır
     * 
     * İşlem tipine göre hesap bakiyelerini eski haline getirir.
     * Orijinal işlem detaylarını kullanarak geri alma işlemini yapar.
     * 
     * @param Transaction $transaction Geri alınacak işlem
     */
    public function revertTransaction(Transaction $transaction): void
    {
        $originalType = $transaction->getOriginal('type');
        $originalSourceAccountId = $transaction->getOriginal('source_account_id');
        $originalDestinationAccountId = $transaction->getOriginal('destination_account_id');
        $originalAmount = (float) $transaction->getOriginal('amount');
        $originalCurrency = (string) $transaction->getOriginal('currency');
        $originalDate = Carbon::parse($transaction->getOriginal('date'));

        if ($originalAmount <= 0) { return; }

        match ($originalType) {
            TransactionTypeEnum::EXPENSE->value => $this->revertExpense($originalSourceAccountId, $originalAmount, $originalCurrency, $originalDate),
            TransactionTypeEnum::INCOME->value => $this->revertIncome($originalDestinationAccountId, $originalAmount, $originalCurrency, $originalDate),
            TransactionTypeEnum::TRANSFER->value => $this->revertTransfer($originalSourceAccountId, $originalDestinationAccountId, $originalAmount, $originalCurrency, $originalDate),
            TransactionTypeEnum::INSTALLMENT->value => $this->revertInstallment($originalSourceAccountId, $originalAmount, $originalCurrency, $originalDate),
            TransactionTypeEnum::SUBSCRIPTION->value => $this->revertSubscription($originalSourceAccountId, $originalAmount, $originalCurrency, $originalDate),
            TransactionTypeEnum::LOAN_PAYMENT->value => $this->revertLoanPayment($originalSourceAccountId, $originalAmount, $originalCurrency, $originalDate),
            default => null,
        };
    }

    /**
     * Gider işlemini geri alır
     *
     * @param int|null $oldSourceAccountId
     * @param float $originalAmount
     * @param string $originalCurrency
     * @param Carbon $originalDate
     */
    private function revertExpense(?int $oldSourceAccountId, float $originalAmount, string $originalCurrency, Carbon $originalDate): void
    {
        if ($oldSourceAccountId) {
            $oldSourceAccount = Account::find($oldSourceAccountId);
            if ($oldSourceAccount) {
                $revertAmount = 0.0;
                // Kredi kartı işlemi ise borç azalt (negatif ekle)
                if ($oldSourceAccount->type === Account::TYPE_CREDIT_CARD) {
                    $revertAmount = -$originalAmount; 
                } else {
                    // Normal hesap ise bakiye artır (pozitif ekle)
                    $revertAmount = $originalAmount;
                }

                if ($revertAmount != 0) {
                    $this->updateBalance(
                        $oldSourceAccount,
                        $revertAmount,
                        $originalCurrency,
                        $originalDate
                    );
                }
            }
        }
    }

    /**
     * Gelir işlemini geri alır
     *
     * @param int|null $oldDestinationAccountId
     * @param float $originalAmount
     * @param string $originalCurrency
     * @param Carbon $originalDate
     */
    private function revertIncome(?int $oldDestinationAccountId, float $originalAmount, string $originalCurrency, Carbon $originalDate): void
    {
        if ($oldDestinationAccountId) {
            $oldDestinationAccount = Account::find($oldDestinationAccountId);
            if ($oldDestinationAccount) {
                $revertAmount = -$originalAmount;

                $this->updateBalance(
                    $oldDestinationAccount,
                    $revertAmount,
                    $originalCurrency,
                    $originalDate
                );
            }
        }
    }

    /**
     * Transfer işlemini geri alır
     *
     * @param int|null $oldSourceAccountId
     * @param int|null $oldDestinationAccountId
     * @param float $originalAmount
     * @param string $originalCurrency Currency of the original amount
     * @param Carbon $originalDate
     */
    private function revertTransfer(?int $oldSourceAccountId, ?int $oldDestinationAccountId, float $originalAmount, string $originalCurrency, Carbon $originalDate): void
    {
        // Revert source account
        if ($oldSourceAccountId) {
            $oldSourceAccount = Account::find($oldSourceAccountId);
            if ($oldSourceAccount) {
                $this->updateBalance(
                    $oldSourceAccount,
                    $originalAmount, 
                    $originalCurrency, 
                    $originalDate
                );
            }
        }

        // Revert destination account
        if ($oldDestinationAccountId) {
            $oldDestinationAccount = Account::find($oldDestinationAccountId);
            if ($oldDestinationAccount) {
                $this->updateBalance(
                    $oldDestinationAccount,
                    -$originalAmount, 
                    $originalCurrency, 
                    $originalDate
                );
            }
        }
    }

    /**
     * Taksit işlemini geri alır
     *
     * @param int|null $oldSourceAccountId
     * @param float $originalAmount
     * @param string $originalCurrency
     * @param Carbon $originalDate
     */
    private function revertInstallment(?int $oldSourceAccountId, float $originalAmount, string $originalCurrency, Carbon $originalDate): void
    {
        if ($oldSourceAccountId) {
            $oldSourceAccount = Account::find($oldSourceAccountId);
            if ($oldSourceAccount) {
                $revertAmount = $originalAmount;

                $this->updateBalance(
                    $oldSourceAccount,
                    $revertAmount, 
                    $originalCurrency,
                    $originalDate
                );
            }
        }
    }

    /**
     * Abonelik işlemini geri alır (Gider gibi davranır)
     *
     * @param int|null $oldSourceAccountId
     * @param float $originalAmount
     * @param string $originalCurrency
     * @param Carbon $originalDate
     */
    private function revertSubscription(?int $oldSourceAccountId, float $originalAmount, string $originalCurrency, Carbon $originalDate): void
    {
        $this->revertExpense($oldSourceAccountId, $originalAmount, $originalCurrency, $originalDate);
    }

    /**
     * Kredi ödemesi işlemini geri alır
     *
     * @param int|null $oldSourceAccountId
     * @param float $originalAmount
     * @param string $originalCurrency
     * @param Carbon $originalDate
     */
    private function revertLoanPayment(?int $oldSourceAccountId, float $originalAmount, string $originalCurrency, Carbon $originalDate): void
    {
        if ($oldSourceAccountId) {
            $oldSourceAccount = Account::find($oldSourceAccountId);
            if ($oldSourceAccount) {
                $revertAmount = 0.0;
                // Kredi kartı işlemi ise borç azalt (negatif ekle)
                if ($oldSourceAccount->type === Account::TYPE_CREDIT_CARD) {
                    $revertAmount = -$originalAmount; 
                } else {
                    // Normal hesap ise bakiye artır (pozitif ekle)
                    $revertAmount = $originalAmount;
                }

                if ($revertAmount != 0) {
                    $this->updateBalance(
                        $oldSourceAccount,
                        $revertAmount,
                        $originalCurrency,
                        $originalDate
                    );
                }
            }
        }
    }

    /**
     * Hesap bakiyesini günceller ve para birimi dönüşümünü yapar
     *
     * @param Account $account Güncellenecek hesap
     * @param float $amount Eklenecek/Çıkarılacak tutar (işareti önemlidir)
     * @param string $transactionCurrency İşlemin yapıldığı para birimi
     * @param Carbon $date İşlem tarihi (kur dönüşümü için)
     */
    private function updateBalance(Account $account, float $amount, string $transactionCurrency, Carbon $date): void
    {
        if ($amount == 0) { 
            return;
        }

        $adjustmentAmount = 0.0;

        // Hesap para birimi ile işlem para birimi aynıysa direkt amount kullan
        if ($account->currency === $transactionCurrency) {
            $adjustmentAmount = $amount;
        } else {
            // Farklı para birimlerinde convert kullan (tutarın işareti korunur)
            $convertedAmount = $this->currencyService->convert(
                $amount,
                $transactionCurrency,
                $account->currency,
                $date
            );
            $adjustmentAmount = (float) $convertedAmount;
        }

        // Bakiyeye ayarlama miktarını ekle/çıkar
        $account->balance += $adjustmentAmount;

        // Bakiyeyi 2 ondalık basamağa yuvarla ve kaydet
        $account->balance = round($account->balance, 2);
        $account->save();
    }

    /**
     * Hesabın yeterli bakiyeye sahip olup olmadığını kontrol eder
     * 
     * @param Account $account Kontrol edilecek hesap
     * @param float $amount Gerekli tutar
     * @param string $currency İşlem para birimi
     * @return bool Yeterli bakiye varsa true
     */
    public function hasEnoughBalance(Account $account, float $amount, string $currency): bool
    {
        // Hesap para birimi ile işlem para birimi aynıysa direkt karşılaştır
        if ($account->currency === $currency) {
            return $account->balance >= $amount;
        }

        // Farklı para birimlerinde try_equivalent kullan
        $accountBalanceInTRY = $this->currencyService->convertToTRY($account->balance, $account->currency);
        $amountInTRY = $this->currencyService->convertToTRY($amount, $currency);

        // Bakiyeleri 2 ondalık basamağa yuvarla
        $accountBalanceInTRY = round($accountBalanceInTRY, 2);
        $amountInTRY = round($amountInTRY, 2);

        return $accountBalanceInTRY >= $amountInTRY;
    }

    /**
     * Hesabın mevcut bakiyesini belirtilen para biriminde döndürür
     * 
     * @param Account $account Hesap
     * @param string $currency İstenen para birimi
     * @return float Hesap bakiyesi
     */
    public function getAvailableBalance(Account $account, string $currency): float
    {
        // Hesap para birimi ile işlem para birimi aynıysa direkt bakiyeyi döndür
        if ($account->currency === $currency) {
            return round($account->balance, 2);
        }

        // Farklı para birimlerinde try_equivalent kullan
        $balanceInTRY = $this->currencyService->convertToTRY($account->balance, $account->currency);
        return round($balanceInTRY, 2);
    }
} 