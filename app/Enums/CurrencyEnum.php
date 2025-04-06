<?php

namespace App\Enums;

/**
 * Para Birimi Enum Sınıfı
 * 
 * Sistemde desteklenen para birimlerini tanımlar.
 * Her para birimi için renk ve sembol bilgilerini içerir.
 */
enum CurrencyEnum: string
{
    /** Türk Lirası */
    case TRY = 'TRY';
    /** Amerikan Doları */
    case USD = 'USD';
    /** Euro */
    case EUR = 'EUR';
    /** İngiliz Sterlini */
    case GBP = 'GBP';

    /**
     * Para biriminin görsel temsili için renk kodunu döndürür
     * 
     * @return string RGB renk kodu
     */
    public function color(): string
    {
        return match($this) {
            self::TRY => 'rgb(230, 25, 75)',  // Kırmızı
            self::USD => 'rgb(60, 180, 75)',   // Yeşil
            self::EUR => 'rgb(0, 130, 200)',   // Mavi
            self::GBP => 'rgb(245, 130, 48)',  // Turuncu
        };
    }

    /**
     * Para biriminin sembolünü döndürür
     * 
     * @return string Para birimi sembolü
     */
    public function symbol(): string
    {
        return match($this) {
            self::TRY => '₺',
            self::USD => '$',
            self::EUR => '€',
            self::GBP => '£',
        };
    }
} 