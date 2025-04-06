<?php

declare(strict_types=1);

namespace App\Services\Currency;

use Illuminate\Support\Carbon;

/**
 * Para birimi dönüşüm servisi
 * 
 * Farklı para birimleri arasında dönüşüm işlemlerini gerçekleştirir.
 * TRY bazlı dönüşüm yaparak çapraz kurları hesaplar.
 */
class CurrencyConversionService
{
    private CurrencyService $currencyService;

    /**
     * @param CurrencyService $currencyService Döviz kuru servisi
     */
    public function __construct(CurrencyService $currencyService)
    {
        $this->currencyService = $currencyService;
    }

    /**
     * Bir tutarı bir para biriminden diğerine çevirir
     * 
     * @param float $amount Dönüştürülecek tutar
     * @param string $fromCurrency Kaynak para birimi
     * @param string $toCurrency Hedef para birimi
     * @param Carbon $date İşlem tarihi
     * @return float Dönüştürülmüş tutar
     */
    public function convert(float $amount, string $fromCurrency, string $toCurrency, Carbon $date): float
    {
        // Aynı para birimi ise direkt dön
        if ($fromCurrency === $toCurrency) {
            return $amount;
        }

        // TRY'ye çevir
        $tryAmount = $this->convertToTRY($amount, $fromCurrency, $date);
        
        // TRY'den hedef para birimine çevir
        return $this->convertFromTRY($tryAmount, $toCurrency, $date);
    }

    /**
     * Bir tutarı TRY'ye çevirir
     * 
     * @param float $amount Dönüştürülecek tutar
     * @param string $fromCurrency Kaynak para birimi
     * @param Carbon $date İşlem tarihi
     * @return float TRY cinsinden tutar
     */
    private function convertToTRY(float $amount, string $fromCurrency, Carbon $date): float
    {
        if ($fromCurrency === 'TRY') {
            return $amount;
        }

        $rates = $this->currencyService->getExchangeRates($date);
        if (!isset($rates[$fromCurrency])) {
            throw new \Exception("Kur bilgisi bulunamadı: {$fromCurrency}");
        }

        return $amount * $rates[$fromCurrency]['buying'];
    }

    /**
     * TRY tutarını başka bir para birimine çevirir
     * 
     * @param float $tryAmount TRY cinsinden tutar
     * @param string $toCurrency Hedef para birimi
     * @param Carbon $date İşlem tarihi
     * @return float Dönüştürülmüş tutar
     */
    private function convertFromTRY(float $tryAmount, string $toCurrency, Carbon $date): float
    {
        if ($toCurrency === 'TRY') {
            return $tryAmount;
        }

        $rates = $this->currencyService->getExchangeRates($date);
        if (!isset($rates[$toCurrency])) {
            throw new \Exception("Kur bilgisi bulunamadı: {$toCurrency}");
        }

        return $tryAmount / $rates[$toCurrency]['selling'];
    }

    /**
     * Hesap bakiyesi için düşülecek tutarı hesaplar
     * 
     * @param float $amount İşlem tutarı
     * @param string $transactionCurrency İşlem para birimi
     * @param string $accountCurrency Hesap para birimi
     * @param Carbon $date İşlem tarihi
     * @return float Hesaptan düşülecek tutar
     */
    public function calculateAccountDeduction(
        float $amount,
        string $transactionCurrency,
        string $accountCurrency,
        Carbon $date
    ): float {
        return $this->convert($amount, $transactionCurrency, $accountCurrency, $date);
    }
} 