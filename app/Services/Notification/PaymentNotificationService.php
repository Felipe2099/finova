<?php

namespace App\Services\Notification;

use App\Models\Account;
use App\Models\Debt;
use App\Models\Loan;
use App\Models\Setting;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Payment notification service
 * 
 * Sends notifications for upcoming payments.
 */
class PaymentNotificationService
{
    protected TelegramNotificationService $telegramService;

    /**
     * @param TelegramNotificationService $telegramService Telegram notification service
     */
    public function __construct(TelegramNotificationService $telegramService)
    {
        $this->telegramService = $telegramService;
    }

    /**
     * Send notifications for upcoming payments.
     */
    public function sendUpcomingPaymentNotifications(int $days = 3): void
    {
        // Credit card statement notifications
        if ($this->isNotificationEnabled('notify_credit_card_statement')) {
            $this->notifyCreditCardStatements($days);
        }
        
        // Recurring payment notifications
        if ($this->isNotificationEnabled('notify_recurring_payment')) {
            $this->notifyRecurringTransactions($days);
        }
        
        // Debt & Receivable notifications
        if ($this->isNotificationEnabled('notify_debt_receivable')) {
            $this->notifyDebtPayments($days);
        }
        
        // Loan payment notifications
        if ($this->isNotificationEnabled('notify_loan_payment')) {
            $this->notifyLoanPayments($days);
        }
    }

    /**
     * Check if a specific notification type is enabled.
     */
    private function isNotificationEnabled(string $key): bool
    {
        $setting = Setting::where('group', 'notification')
            ->where('key', $key)
            ->first();
            
        return $setting && filter_var($setting->value, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Credit card statement notifications
     * The accounts table must have type credit_card and check the statement_day (Account statement day)
     */
    protected function notifyCreditCardStatements(int $days): void
    {
        $today = Carbon::today();
        $thresholdDate = $today->copy()->addDays($days);
        
        $creditCards = Account::where('type', Account::TYPE_CREDIT_CARD)
            ->where('status', true)
            ->get();
        
        $upcomingStatements = $creditCards->filter(function ($card) use ($today, $thresholdDate) {
            $statementDay = (int) ($card->details['statement_day'] ?? 0);
            
            if ($statementDay <= 0) {
                return false;
            }
            
            // Calculate the next account statement date
            $nextStatementDate = $today->copy()->startOfMonth()->setDay($statementDay);
            if ($today->day >= $statementDay) {
                $nextStatementDate->addMonth();
            }
            
            // Is it before the threshold date?
            return $nextStatementDate->between($today, $thresholdDate);
        });
        
        if ($upcomingStatements->isNotEmpty()) {
            $message = $this->formatCreditCardStatementMessage($upcomingStatements);
            $this->telegramService->send($message);
        }
    }

    /**
     * Recurring transaction notifications
     * The transactions table must have is_subscription=1 and check the next_payment_date
     */
    protected function notifyRecurringTransactions(int $days): void
    {
        $today = Carbon::today();
        $thresholdDate = $today->copy()->addDays($days);
        
        $recurringTransactions = Transaction::where('is_subscription', true)
            ->whereNotNull('next_payment_date')
            ->whereDate('next_payment_date', '>=', $today)
            ->whereDate('next_payment_date', '<=', $thresholdDate)
            ->get();
        
        if ($recurringTransactions->isNotEmpty()) {
            $message = $this->formatRecurringTransactionsMessage($recurringTransactions);
            $this->telegramService->send($message);
        }
    }

    /**
     * Debt & Receivable notifications
     * The debts table must have due_date and status=pending
     */
    protected function notifyDebtPayments(int $days): void
    {
        $today = Carbon::today();
        $thresholdDate = $today->copy()->addDays($days);
        
        $debts = Debt::whereNotNull('due_date')
            ->whereDate('due_date', '>=', $today)
            ->whereDate('due_date', '<=', $thresholdDate)
            ->where('status', 'pending')
            ->get();
        
        if ($debts->isNotEmpty()) {
            $message = $this->formatDebtPaymentsMessage($debts);
            $this->telegramService->send($message);
        }
    }

    /**
     * Loan payment notifications
     * The loans table must have next_payment_date and status=pending
     */
    protected function notifyLoanPayments(int $days): void
    {
        $today = Carbon::today();
        $thresholdDate = $today->copy()->addDays($days);
        
        $loans = Loan::whereNotNull('next_payment_date')
            ->whereDate('next_payment_date', '>=', $today)
            ->whereDate('next_payment_date', '<=', $thresholdDate)
            ->where('status', 'pending')
            ->get();
        
        if ($loans->isNotEmpty()) {
            $message = $this->formatLoanPaymentsMessage($loans);
            $this->telegramService->send($message);
        }
    }

    /**
     * Format the credit card statement message
     */
    protected function formatCreditCardStatementMessage(Collection $creditCards): string
    {
        $message = "<b>🔔 Yaklaşan Kredi Kartı Hesap Kesim Bildirimi</b>\n\n";
        
        foreach ($creditCards as $card) {
            $statementDay = (int) ($card->details['statement_day'] ?? 0);
            $bankName = $card->details['bank_name'] ?? 'Banka';
            $nextStatementDate = Carbon::today()->startOfMonth()->setDay($statementDay);
            
            if (Carbon::today()->day >= $statementDay) {
                $nextStatementDate->addMonth();
            }
            
            $message .= "💳 <b>{$card->name} ({$bankName})</b>\n";
            $message .= "Hesap Kesim: {$nextStatementDate->format('d.m.Y')}\n";
            $message .= "Toplam Borç: {$card->balance} {$card->currency}\n\n";
        }
        
        return $message;
    }

    /**
     * Format the recurring transaction message
     */
    protected function formatRecurringTransactionsMessage(Collection $transactions): string
    {
        $message = "<b>🔄 Yaklaşan Devamlı İşlemler Bildirimi</b>\n\n";
        
        foreach ($transactions as $transaction) {
            $typeText = $transaction->type === 'income' ? 'Gelir' : 'Gider';
            $message .= "📅 <b>{$transaction->description}</b>\n";
            $message .= "Tür: {$typeText}\n";
            $message .= "Tutar: {$transaction->amount} {$transaction->currency}\n";
            $message .= "Tarih: {$transaction->next_payment_date->format('d.m.Y')}\n\n";
        }
        
        return $message;
    }

    /**
     * Format the debt & receivable payment message
     */
    protected function formatDebtPaymentsMessage(Collection $debts): string
    {
        $message = "<b>💰 Yaklaşan Borç/Alacak Ödemeleri Bildirimi</b>\n\n";
        
        foreach ($debts as $debt) {
            $typeText = $debt->type === 'receivable' ? 'Alacak' : 'Borç';
            $name = $debt->type === 'receivable' 
                ? ($debt->customer->name ?? 'Müşteri') 
                : ($debt->supplier->name ?? 'Tedarikçi');
            
            $message .= "📝 <b>{$typeText}: {$name}</b>\n";
            $message .= "Açıklama: {$debt->description}\n";
            $message .= "Tutar: {$debt->getRemainingAmountAttribute()} {$debt->currency}\n";
            $message .= "Son Ödeme: {$debt->due_date->format('d.m.Y')}\n\n";
        }
        
        return $message;
    }

    /**
     * Format the loan payment message
     */
    protected function formatLoanPaymentsMessage(Collection $loans): string
    {
        $message = "<b>🏦 Yaklaşan Kredi Ödemeleri Bildirimi</b>\n\n";
        
        foreach ($loans as $loan) {
            $message .= "💵 <b>{$loan->bank_name} - {$loan->loan_type}</b>\n";
            $message .= "Aylık Ödeme: {$loan->monthly_payment}\n";
            $message .= "Kalan Borç: {$loan->remaining_amount}\n";
            $message .= "Sonraki Ödeme: {$loan->next_payment_date->format('d.m.Y')}\n\n";
        }
        
        return $message;
    }
} 