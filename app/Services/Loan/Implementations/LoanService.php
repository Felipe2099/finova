<?php

declare(strict_types=1);

namespace App\Services\Loan\Implementations;

use App\Models\Loan;
use App\Models\Transaction;
use App\Models\Account;
use App\Services\Loan\Contracts\LoanServiceInterface;
use App\DTOs\Loan\LoanData;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use App\Services\Payment\Implementations\PaymentService;
use App\Enums\PaymentMethodEnum;

/**
 * Kredi servisi implementasyonu
 * 
 * Kredi işlemlerinin yönetimi için gerekli metodları içerir.
 * Kredi kayıtlarının oluşturulması, güncellenmesi, silinmesi ve ödeme işlemlerini gerçekleştirir.
 */
final class LoanService implements LoanServiceInterface
{
    private PaymentService $paymentService;

    /**
     * @param PaymentService $paymentService Ödeme servisi
     */
    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * Yeni bir kredi kaydı oluşturur
     * 
     * @param array $data Kredi verileri
     * @return Loan Oluşturulan kredi kaydı
     */
    public function createLoan(array $data): Loan
    {
        if (!isset($data['amount']) || empty($data['amount']) || !is_numeric($data['amount'])) {
            throw new \Exception('Tutar (Amount) gereklidir ve geçerli bir sayı olmalıdır.');
        }
        $loanData = LoanData::fromArray($data);

        return DB::transaction(function () use ($loanData) {
            $loan = Loan::create([
                'user_id' => $loanData->user_id,
                'bank_name' => $loanData->bank_name,
                'loan_type' => $loanData->loan_type,
                'amount' => $loanData->amount,
                'monthly_payment' => $loanData->monthly_payment,
                'installments' => $loanData->installments,
                'remaining_installments' => $loanData->remaining_installments,
                'start_date' => $loanData->start_date,
                'next_payment_date' => $loanData->next_payment_date,
                'due_date' => Carbon::parse($loanData->start_date)->addMonths($loanData->installments),
                'remaining_amount' => $loanData->monthly_payment * $loanData->remaining_installments,
                'status' => $loanData->status,
                'notes' => $loanData->notes,
            ]);
            
            return $loan;
        });
    }

    /**
     * Mevcut bir kredi kaydını günceller
     * 
     * @param Loan $loan Güncellenecek kredi kaydı
     * @param LoanData $data Yeni kredi verileri
     * @return Loan Güncellenmiş kredi kaydı
     */
    public function update(Loan $loan, LoanData $data): Loan
    {
        if (!isset($data->amount) || !is_numeric($data->amount)) {
            throw new \Exception('Tutar (Amount) gereklidir ve geçerli bir sayı olmalıdır.');
        }
        return DB::transaction(function () use ($loan, $data) {
            $loan->update([
                'bank_name' => $data->bank_name,
                'loan_type' => $data->loan_type,
                'amount' => floatval($data->amount),
                'monthly_payment' => floatval($data->monthly_payment),
                'installments' => intval($data->installments),
                'remaining_installments' => intval($data->remaining_installments),
                'start_date' => $data->start_date,
                'next_payment_date' => $data->next_payment_date,
                'due_date' => Carbon::parse($data->start_date)->addMonths($data->installments),
                'status' => $data->status,
                'notes' => $data->notes,
            ]);
            
            $loan->remaining_amount = $loan->monthly_payment * $loan->remaining_installments;
            $loan->save();
            
            return $loan->fresh();
        });
    }

    /**
     * Kredi kaydına ödeme ekler
     * 
     * @param Loan $loan Ödeme eklenecek kredi kaydı
     * @param array $data Ödeme verileri
     */
    public function addPayment(Loan $loan, array $data): void
    {
        if (!isset($data['amount']) || !is_numeric($data['amount'])) {
            throw new \Exception('Ödeme tutarı gereklidir ve geçerli bir sayı olmalıdır.');
        }
        
        // Kredi tamamen ödenmiş mi kontrol et
        if ($loan->status === 'paid' || $loan->remaining_installments <= 0) {
            throw new \Exception('Bu kredi zaten tamamen ödenmiş durumda.');
        }
        
        // Taksit numarasını belirle
        $installmentNumber = $loan->installments - $loan->remaining_installments + 1;
        
        // Kredi türünü Türkçeleştir
        $loanTypeText = $loan->loan_type === 'business' ? 'Ticari' : 'Bireysel';
        
        // Açıklama oluştur
        $data['description'] = $loan->bank_name . " " . $loanTypeText . " Kredi Ödemesi - Taksit " . $installmentNumber . "/" . $loan->installments;
        $data['installment_number'] = $installmentNumber;
        
        // Ödeme yöntemine göre işlem yap
        if (in_array($data['payment_method'], [PaymentMethodEnum::BANK->value, PaymentMethodEnum::CREDIT_CARD->value]) && empty($data['account_id'])) {
            $accountType = $data['payment_method'] === PaymentMethodEnum::BANK->value ? 'Banka hesabı' : 'Kredi kartı';
            throw new \Exception($accountType . ' seçilmelidir.');
        }
        
        // Ödeme işlemini gerçekleştir
        $transaction = Transaction::create([
            'user_id' => $loan->user_id,
            'amount' => $data['amount'],
            'type' => 'loan_payment',
            'currency' => 'TRY', // Kredi ödemeleri sadece TL
            'try_equivalent' => $data['amount'], // TRY para birimi kullanıldığından amount ile aynı
            'description' => $data['description'],
            'date' => $data['payment_date'] ?? now()->format('Y-m-d'),
            'transaction_date' => $data['payment_date'] ?? now()->format('Y-m-d'),
            'status' => 'completed',
            'payment_method' => $data['payment_method'],
            'source_account_id' => $data['account_id'] ?? null,
            'related_id' => $loan->id,
            'related_type' => Loan::class,
        ]);
        
        // Hesap bakiyesini güncelle
        if (!empty($data['account_id'])) {
            $account = Account::findOrFail($data['account_id']);
            
            // Ödeme yöntemine göre bakiyeyi güncelle
            if ($account->type === Account::TYPE_CREDIT_CARD) {
                // Kredi kartı ile ödeme yapılıyorsa borç artar (bakiye artar, positive amount)
                $account->balance += (float)$data['amount'];
            } else {
                // Normal banka hesabı ise bakiye azalır (negative amount)
                $account->balance -= (float)$data['amount'];
            }
            
            $account->save();
        }
        
        // Kalan taksit sayısını güncelle
        $loan->remaining_installments -= 1;
        
        // Sonraki ödeme tarihini güncelle
        if ($loan->remaining_installments > 0) {
            $loan->next_payment_date = Carbon::parse($loan->next_payment_date)->addMonth()->format('Y-m-d');
        }
        
        // Kalan tutarı güncelle
        $loan->remaining_amount = $loan->monthly_payment * $loan->remaining_installments;
        
        // Kredi durumunu güncelle
        if ($loan->remaining_installments <= 0) {
            $loan->status = 'paid';
            $loan->remaining_amount = 0;
        } else if ($loan->status === 'overdue') {
            $loan->status = 'active';
        }
        
        $loan->save();
    }

    /**
     * Kredi kaydını siler
     * 
     * @param Loan $loan Silinecek kredi kaydı
     * @return array İşlem sonucu ve mesaj
     */
    public function delete(Loan $loan): array
    {
        try {
            DB::transaction(function () use ($loan) {
                // İlişkili işlemleri manuel olarak bul ve sil
                $transactions = Transaction::where('description', 'like', '%Kredi Ödemesi%')
                    ->where(function ($query) use ($loan) {
                        $query->where('description', 'like', '%' . $loan->bank_name . '%')
                              ->orWhere('description', 'like', '%' . $loan->id . '%');
                    })
                    ->where('user_id', $loan->user_id)
                    ->get();
                
                foreach ($transactions as $transaction) {
                    $transaction->delete();
                }
                
                // Krediyi sil
                $loan->delete();
            });
            
            return [
                'success' => true,
                'message' => 'Kredi başarıyla silindi.'
            ];
        } catch (\Exception $e) {
            // Ödeme yapılmış krediler silinemez, bilgilendirme mesajı döndür
            return [
                'success' => false,
                'message' => 'Ödeme yapılmış krediler silinemez. Lütfen önce ödemeleri silin.'
            ];
        }
    }

    /**
     * Kalan tutarı hesaplar
     * 
     * @param Loan $loan Hesaplanacak kredi kaydı
     * @return float Kalan tutar
     */
    private function calculateRemainingAmount(Loan $loan): float
    {
        return $loan->monthly_payment * $loan->remaining_installments;
    }
    
    /**
     * Durum metnini getirir
     * 
     * @param string $status Durum kodu
     * @return string Durum metni
     */
    private function getStatusText(string $status): string
    {
        return match($status) {
            'paid' => 'Ödendi',
            'active' => 'Aktif',
            'pending' => 'Bekliyor',
            'overdue' => 'Gecikmiş',
            default => 'Bilinmiyor'
        };
    }
}