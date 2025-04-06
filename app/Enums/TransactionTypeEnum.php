<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * İşlem Türü Enum Sınıfı
 * 
 * Sistemde desteklenen finansal işlem türlerini tanımlar.
 * Her işlem türü için Türkçe etiket içerir.
 */
enum TransactionTypeEnum: string
{
    /** Gelir */
    case INCOME = 'income';
    /** Gider */
    case EXPENSE = 'expense';
    /** Transfer */
    case TRANSFER = 'transfer';
    /** Taksitli Ödeme */
    case INSTALLMENT = 'installment';
    /** Abonelik */
    case SUBSCRIPTION = 'subscription';
    /** Kredi Ödemesi */
    case LOAN_PAYMENT = 'loan_payment';

    /**
     * İşlem türünün Türkçe etiketini döndürür
     * 
     * @return string Türkçe etiket
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