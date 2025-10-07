<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Transaction Type Enum Class
 * 
 * Defines the supported financial transaction types.
 * Contains the Turkish label for each transaction type.
 */
enum TransactionTypeEnum: string
{
    /** Income */
    case INCOME = 'income';
    /** Expense */
    case EXPENSE = 'expense';
    /** Transfer */
    case TRANSFER = 'transfer';
    /** Installment */
    case INSTALLMENT = 'installment';
    /** Abonelik */
    case SUBSCRIPTION = 'subscription';
    /** Loan Payment */
    case LOAN_PAYMENT = 'loan_payment';

    /**
     * Returns the Turkish label for the transaction type
     * 
     * @return string Turkish label
     */
    public function label(): string
    {
        return match($this) {
            self::INCOME => 'Gelir',
            self::EXPENSE => 'Gider',
            self::TRANSFER => 'Transfer',
            self::INSTALLMENT => 'Taksitli Ödeme',
            self::SUBSCRIPTION => 'Abonelik',
            self::LOAN_PAYMENT => 'Kredi Ödemesi',
        };
    }
} 