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
 * Loan service implementation
 * 
 * Contains methods required to manage loan operations.
 * Handles creating, updating, deleting, and processing payments for loan records.
 */
final class LoanService implements LoanServiceInterface
{
    private PaymentService $paymentService;

    /**
     * @param PaymentService $paymentService Payment service
     */
    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * Create a new loan record.
     * 
     * @param array $data Loan data
     * @return Loan Created loan record
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
     * Update an existing loan record.
     * 
     * @param Loan $loan Loan record to update
     * @param LoanData $data New loan data
     * @return Loan Updated loan record
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
     * Add a payment to a loan record.
     * 
     * @param Loan $loan Loan record to add payment to
     * @param array $data Payment data
     */
    public function addPayment(Loan $loan, array $data): void
    {
        if (!isset($data['amount']) || !is_numeric($data['amount'])) {
            throw new \Exception('Ödeme tutarı gereklidir ve geçerli bir sayı olmalıdır.');
        }
        
        // Check if the loan is fully paid
        if ($loan->status === 'paid' || $loan->remaining_installments <= 0) {
            throw new \Exception('Bu kredi zaten tamamen ödenmiş durumda.');
        }
        
        // Determine the installment number
        $installmentNumber = $loan->installments - $loan->remaining_installments + 1;
        
        // Translate the loan type
        $loanTypeText = $loan->loan_type === 'business' ? 'Ticari' : 'Bireysel';
        
        // Create description
        $data['description'] = $loan->bank_name . " " . $loanTypeText . " Kredi Ödemesi - Taksit " . $installmentNumber . "/" . $loan->installments;
        $data['installment_number'] = $installmentNumber;
        
        // Process payment based on payment method
        if (in_array($data['payment_method'], [PaymentMethodEnum::BANK->value, PaymentMethodEnum::CREDIT_CARD->value]) && empty($data['account_id'])) {
            $accountType = $data['payment_method'] === PaymentMethodEnum::BANK->value ? 'Banka hesabı' : 'Kredi kartı';
            throw new \Exception($accountType . ' seçilmelidir.');
        }
        
        // Process payment
        $transaction = Transaction::create([
            'user_id' => $loan->user_id,
            'amount' => $data['amount'],
            'type' => 'loan_payment',
            'currency' => 'TRY', // Loan payments are only in TRY
            'try_equivalent' => $data['amount'], 
            'description' => $data['description'],
            'date' => $data['payment_date'] ?? now()->format('Y-m-d'),
            'transaction_date' => $data['payment_date'] ?? now()->format('Y-m-d'),
            'status' => 'completed',
            'payment_method' => $data['payment_method'],
            'source_account_id' => $data['account_id'] ?? null,
            'related_id' => $loan->id,
            'related_type' => Loan::class,
        ]);
        
        // Update account balance
        if (!empty($data['account_id'])) {
            $account = Account::findOrFail($data['account_id']);
            
            // Update account balance based on payment method
            if ($account->type === Account::TYPE_CREDIT_CARD) {
                // If payment is made with credit card, the debt increases (balance increases, positive amount)
                $account->balance += (float)$data['amount'];
            } else {
                // If payment is made with bank account, the balance decreases (negative amount)
                $account->balance -= (float)$data['amount'];
            }
            
            $account->save();
        }
        
        // Update remaining installments
        $loan->remaining_installments -= 1;
        
        // Update next payment date
        if ($loan->remaining_installments > 0) {
            $loan->next_payment_date = Carbon::parse($loan->next_payment_date)->addMonth()->format('Y-m-d');
        }
        
        // Update remaining amount
        $loan->remaining_amount = $loan->monthly_payment * $loan->remaining_installments;
        
        // Update loan status
        if ($loan->remaining_installments <= 0) {
            $loan->status = 'paid';
            $loan->remaining_amount = 0;
        } else if ($loan->status === 'overdue') {
            $loan->status = 'active';
        }
        
        $loan->save();
    }

    /**
     * Delete a loan record.
     * 
     * @param Loan $loan Loan record to delete
     * @return array Result and message
     */
    public function delete(Loan $loan): array
    {
        try {
            DB::transaction(function () use ($loan) {
                // Find and delete associated transactions manually
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
                
                // Delete loan
                $loan->delete();
            });
            
            return [
                'success' => true,
                'message' => 'Kredi başarıyla silindi.'
            ];
        } catch (\Exception $e) {
            // Paid loans cannot be deleted, return a notification message
            return [
                'success' => false,
                'message' => 'Ödeme yapılmış krediler silinemez. Lütfen önce ödemeleri silin.'
            ];
        }
    }

    /**
     * Calculate remaining amount.
     * 
     * @param Loan $loan Loan record to calculate remaining amount
     * @return float Remaining amount
     */
    private function calculateRemainingAmount(Loan $loan): float
    {
        return $loan->monthly_payment * $loan->remaining_installments;
    }
    
    /**
     * Get status text.
     * 
     * @param string $status Status code
     * @return string Status text
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