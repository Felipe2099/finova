<?php

declare(strict_types=1);

namespace App\Services\Currency;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use SimpleXMLElement;

/**
 * Döviz kuru servisi
 * 
 * TCMB'den döviz kurlarını çeker, önbellekler ve yönetir.
 * Kur bilgilerini almak ve çapraz kurları hesaplamak için gerekli metodları içerir.
 */
final class CurrencyService
{
    private const CACHE_KEY = 'currency_rates';
    private const CACHE_TTL = 3600; // 1 saat
    private const MAX_RETRY_DAYS = 12; // Maksimum 12 gün geriye bakacak
    
    /**
     * Belirli bir tarih için döviz kurlarını getirir
     * 
     * @param Carbon|null $date Tarih
     * @return array|null Döviz kurları
     */
    public function getExchangeRates(?Carbon $date = null): ?array
    {
        $date = $date ?? now();
        $cacheKey = self::CACHE_KEY . '_' . $date->format('Y-m-d');
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($date) {
            return $this->fetchRatesWithFallback($date);
        });
    }

    /**
     * Belirli bir tarih için döviz kurlarını çeker ve yedek mekanizması uygular
     * 
     * @param Carbon $date Tarih
     * @return array|null Döviz kurları
     */
    private function fetchRatesWithFallback(Carbon $date): ?array
    {
        $tryDate = $date->copy();
        $attempts = 0;

        while ($attempts < self::MAX_RETRY_DAYS) {
            // Hafta sonu kontrolü
            if ($tryDate->isWeekend()) {
                $tryDate = $tryDate->copy()->previous('Friday');
                continue;
            }

            // Resmi tatil veya başka bir nedenle veri yoksa bir önceki güne bak
            $rates = $this->fetchRatesForDate($tryDate);
            
            if ($rates !== null) {

                return $rates;
            }



            $tryDate->subDay();
            $attempts++;
        }

        return $this->getDefaultRates();
    }

    /**
     * Belirli bir tarih için TCMB'den döviz kurlarını çeker
     * 
     * @param Carbon $date Tarih
     * @return array|null Döviz kurları
     */
    private function fetchRatesForDate(Carbon $date): ?array
    {
        try {
            if ($date->isToday()) {
                $url = 'https://www.tcmb.gov.tr/kurlar/today.xml';
            } else {
                // TCMB format: /202403/13032024.xml
                $url = sprintf(
                    'https://www.tcmb.gov.tr/kurlar/%s/%s.xml',
                    $date->format('Y') . $date->format('m'),  // Yıl ve ay: 202403
                    $date->format('d') . $date->format('m') . $date->format('Y')  // GünAyYıl: 13032024
                );
            }

            $response = Http::get($url);
            
            if (!$response->successful()) {
                return null;
            }

            $xml = new SimpleXMLElement($response->body());
            $rates = $this->parseXmlRates($xml);

            return $rates;

        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * XML formatındaki kur verilerini işler
     * 
     * @param SimpleXMLElement $xml XML verisi
     * @return array İşlenmiş kur verileri
     */
    private function parseXmlRates(SimpleXMLElement $xml): array
    {
        $rates = [];
        
        foreach ($xml->Currency as $currency) {
            $code = (string) $currency['CurrencyCode'];
            $buying = (float) $currency->ForexBuying;
            $selling = (float) $currency->ForexSelling;
            
            if ($buying > 0 && $selling > 0) {
                $rates[$code] = [
                    'buying' => $buying,
                    'selling' => $selling,
                    'code' => $code,
                    'name' => (string) $currency->CurrencyName,
                    'unit' => (int) $currency->Unit,
                ];
            }
        }

        return $rates;
    }

    /**
     * Varsayılan döviz kurlarını döndürür
     * 
     * @return array Varsayılan kurlar
     */
    private function getDefaultRates(): array
    {
        return [
            'USD' => [
                'buying' => 36,
                'selling' => 36,
                'code' => 'USD',
                'name' => 'US DOLLAR',
                'unit' => 1,
            ],
            'EUR' => [
                'buying' => 39,
                'selling' => 39,
                'code' => 'EUR',
                'name' => 'EURO',
                'unit' => 1,
            ],
            'GBP' => [
                'buying' => 47,
                'selling' => 47,
                'code' => 'GBP',
                'name' => 'İNGİLİZ STERLİNİ',
                'unit' => 1,
            ],
        ];
    }

    /**
     * Belirli bir para birimi için kur bilgisini getirir
     * 
     * @param string $currencyCode Para birimi kodu
     * @param Carbon|null $date Tarih
     * @return array|null Kur bilgisi
     */
    public function getExchangeRate(string $currencyCode, ?Carbon $date = null): ?array
    {
        // TRY için özel kontrol
        if ($currencyCode === 'TRY') {
            return [
                'buying' => 1,
                'selling' => 1,
                'code' => 'TRY',
                'name' => 'TÜRK LİRASI',
                'unit' => 1,
            ];
        }

        $rates = $this->getExchangeRates($date);
        return $rates[$currencyCode] ?? null;
    }

    /**
     * İki para birimi arasındaki çapraz kuru hesaplar
     * 
     * @param string $fromCurrency Kaynak para birimi
     * @param string $toCurrency Hedef para birimi
     * @param array $rates Kur bilgileri
     * @return float Çapraz kur
     */
    public function calculateCrossRate(string $fromCurrency, string $toCurrency, array $rates): float
    {
        // Aynı para birimi
        if ($fromCurrency === $toCurrency) {
            return 1;
        }

        // TRY -> Diğer
        if ($fromCurrency === 'TRY') {
            return 1 / $rates[$toCurrency]['selling'];
        }

        // Diğer -> TRY
        if ($toCurrency === 'TRY') {
            return $rates[$fromCurrency]['buying'];
        }

        // Diğer -> Diğer (USD üzerinden)
        $fromUsdRate = $rates[$fromCurrency]['buying'] / $rates['USD']['buying'];
        $toUsdRate = $rates[$toCurrency]['selling'] / $rates['USD']['selling'];
        
        return $fromUsdRate / $toUsdRate;
    }
} 