<?php

namespace App\Helpers;

use NumberFormatter;

class CurrencyHelper
{
    private static ?NumberFormatter $formatter = null;

    private static function getFormatter(): NumberFormatter
    {
        if (self::$formatter === null) {
            self::$formatter = new NumberFormatter('sr_Latn_RS', NumberFormatter::CURRENCY);
        }

        return self::$formatter;
    }

    /**
     * Format amount as RSD
     */
    public static function toRSD(float|int|string $amount): string
    {
        $formatted = self::getFormatter()->formatCurrency((float) $amount, 'EUR');

        return str_replace('€', 'RSD', $formatted);
    }

    /**
     * Format amount as EUR
     */
    public static function toEUR(float|int|string $amount): string
    {
        return self::getFormatter()->formatCurrency((float) $amount, 'EUR');
    }

    /**
     * Format amount as USD
     */
    public static function toUSD(float|int|string $amount): string
    {
        return self::getFormatter()->formatCurrency((float) $amount, 'USD');
    }

    /**
     * Format amount with specified currency
     */
    public static function format(float|int|string $amount, string $currency): string
    {
        return match (strtoupper($currency)) {
            'EUR' => self::toEUR($amount),
            'USD' => self::toUSD($amount),
            'RSD' => self::toRSD($amount),
            default => (string) $amount,
        };
    }
}
