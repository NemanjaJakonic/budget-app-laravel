<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'name',
        'amount',
        'type',
        'currency',
        'date',
        'category',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'date' => 'date',
    ];

    public const TYPES = ['income', 'expense'];
    public const CURRENCIES = ['RSD', 'EUR', 'USD'];
    public const CATEGORIES = ['bills', 'food', 'rest'];

    public const CATEGORY_LABELS = [
        'bills' => 'Bills',
        'food' => 'Food',
        'rest' => 'Rest',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getCategoryLabelAttribute(): ?string
    {
        return $this->category ? (self::CATEGORY_LABELS[$this->category] ?? ucfirst($this->category)) : null;
    }

    /**
     * Convert amount to RSD based on currency and exchange rates
     */
    public function getAmountInRsd(array $rates): float
    {
        $amount = (float) $this->amount;

        return match ($this->currency) {
            'EUR' => $amount * $rates['RSD'],
            'USD' => $amount * $rates['USD'] * $rates['RSD'],
            default => $amount,
        };
    }

    /**
     * Convert amount to EUR based on currency and exchange rates
     */
    public function getAmountInEur(array $rates): float
    {
        $amount = (float) $this->amount;

        return match ($this->currency) {
            'EUR' => $amount,
            'USD' => $amount * $rates['USD'],
            'RSD' => $amount / $rates['RSD'],
            default => $amount,
        };
    }

    /**
     * Format amount with currency symbol
     */
    public function getFormattedAmount(): string
    {
        $formatter = new \NumberFormatter('sr_Latn_RS', \NumberFormatter::CURRENCY);

        return match ($this->currency) {
            'EUR' => $formatter->formatCurrency((float) $this->amount, 'EUR'),
            'USD' => $formatter->formatCurrency((float) $this->amount, 'USD'),
            'RSD' => str_replace('€', 'RSD', $formatter->formatCurrency((float) $this->amount, 'EUR')),
            default => (string) $this->amount,
        };
    }
}
