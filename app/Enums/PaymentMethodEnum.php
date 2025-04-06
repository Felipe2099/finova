<?php

namespace App\Enums;

/**
 * Ödeme Yöntemi Enum Sınıfı
 * 
 * Sistemde desteklenen ödeme yöntemlerini tanımlar.
 * Her ödeme yöntemi için Türkçe etiket ve dönüşüm metodları içerir.
 */
enum PaymentMethodEnum: string
{
    /** Nakit Ödeme */
    case CASH = 'cash';
    /** Banka Hesabı */
    case BANK = 'bank';
    /** Kredi Kartı */
    case CREDIT_CARD = 'credit_card';
    /** Kripto Cüzdan */
    case CRYPTO = 'crypto';
    /** Sanal POS */
    case VIRTUAL_POS = 'virtual_pos';

    /**
     * Ödeme yönteminin Türkçe etiketini döndürür
     * 
     * @return string Türkçe etiket
     */
    public function label(): string
    {
        return match($this) {
            self::CASH => 'Nakit',
            self::BANK => 'Banka Hesabı',
            self::CREDIT_CARD => 'Kredi Kartı',
            self::CRYPTO => 'Kripto Cüzdan',
            self::VIRTUAL_POS => 'Sanal POS',
        };
    }

    /**
     * Tüm ödeme yöntemlerini etiketleriyle birlikte dizi olarak döndürür
     * 
     * @return array<string, string> Ödeme yöntemleri ve etiketleri
     */
    public static function toArray(): array
    {
        return [
            self::CASH->value => self::CASH->label(),
            self::BANK->value => self::BANK->label(),
            self::CREDIT_CARD->value => self::CREDIT_CARD->label(),
            self::CRYPTO->value => self::CRYPTO->label(),
            self::VIRTUAL_POS->value => self::VIRTUAL_POS->label(),
        ];
    }
} 