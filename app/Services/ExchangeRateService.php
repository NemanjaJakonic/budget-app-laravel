<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class ExchangeRateService
{
    private const CACHE_KEY = 'exchange_rates';

    private const CACHE_TTL = 60 * 60 * 24 * 30; // 30 days in seconds

    private const DEFAULT_RATES = [
        'RSD' => 117.5,
        'EUR' => 1,
        'USD' => 1.0929802,
    ];

    /**
     * Get exchange rates (from cache or API)
     */
    public function getRates(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            return $this->fetchFromApi() ?? self::DEFAULT_RATES;
        });
    }

    /**
     * Force refresh rates from API
     */
    public function refreshRates(): array
    {
        Cache::forget(self::CACHE_KEY);

        return $this->getRates();
    }

    /**
     * Fetch rates from Fixer API
     */
    private function fetchFromApi(): ?array
    {
        $apiKey = config('services.fixer.api_key');

        if (empty($apiKey)) {
            return null;
        }

        try {
            $response = Http::withHeaders([
                'apikey' => $apiKey,
            ])->get('https://api.apilayer.com/fixer/latest', [
                'symbols' => 'RSD,EUR,USD',
                'base' => 'EUR',
            ]);

            if ($response->successful()) {
                $data = $response->json();
                if ($data['success'] ?? false) {
                    return $data['rates'];
                }
            }
        } catch (\Exception $e) {
            report($e);
        }

        return null;
    }

    /**
     * Convert amount between currencies
     */
    public function convert(float $amount, string $from, string $to): float
    {
        $rates = $this->getRates();

        // Convert to EUR first
        $eurAmount = match ($from) {
            'EUR' => $amount,
            'USD' => $amount / $rates['USD'],
            'RSD' => $amount / $rates['RSD'],
            default => $amount,
        };

        // Convert from EUR to target currency
        return match ($to) {
            'EUR' => $eurAmount,
            'USD' => $eurAmount * $rates['USD'],
            'RSD' => $eurAmount * $rates['RSD'],
            default => $eurAmount,
        };
    }

    /**
     * Convert to RSD
     */
    public function toRsd(float $amount, string $currency): float
    {
        return $this->convert($amount, $currency, 'RSD');
    }

    /**
     * Convert to EUR
     */
    public function toEur(float $amount, string $currency): float
    {
        return $this->convert($amount, $currency, 'EUR');
    }
}
