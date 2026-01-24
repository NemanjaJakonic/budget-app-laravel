<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DeepseekService
{
    private ?string $apiKey;

    private string $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('services.deepseek.api_key');
        $this->baseUrl = config('services.deepseek.base_url', 'https://api.deepseek.com');
    }

    /**
     * Parse a voice transcript into structured transaction data.
     *
     * @return array{name: ?string, amount: ?float, type: ?string, currency: ?string, category: ?string, error: ?string}
     */
    public function parseTransaction(string $transcript): array
    {
        if (empty(trim($transcript))) {
            return [
                'name' => null,
                'amount' => null,
                'type' => null,
                'currency' => null,
                'category' => null,
                'error' => 'Empty transcript provided.',
            ];
        }

        $systemPrompt = $this->getSystemPrompt();

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$this->apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(30)->post($this->baseUrl.'/v1/chat/completions', [
                'model' => 'deepseek-chat',
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $transcript],
                ],
                'temperature' => 0.1,
                'response_format' => ['type' => 'json_object'],
            ]);

            if (! $response->successful()) {
                Log::error('Deepseek API error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return [
                    'name' => null,
                    'amount' => null,
                    'type' => null,
                    'currency' => null,
                    'category' => null,
                    'error' => 'Failed to parse transaction. Please try again.',
                ];
            }

            $content = $response->json('choices.0.message.content');
            $parsed = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return [
                    'name' => null,
                    'amount' => null,
                    'type' => null,
                    'currency' => null,
                    'category' => null,
                    'error' => 'Failed to parse response. Please try again.',
                ];
            }

            return [
                'name' => $parsed['name'] ?? null,
                'amount' => isset($parsed['amount']) ? (float) $parsed['amount'] : null,
                'type' => $parsed['type'] ?? null,
                'currency' => $parsed['currency'] ?? null,
                'category' => $parsed['category'] ?? null,
                'error' => null,
            ];
        } catch (\Exception $e) {
            Log::error('Deepseek API exception', [
                'message' => $e->getMessage(),
                'transcript' => $transcript,
            ]);

            return [
                'name' => null,
                'amount' => null,
                'type' => null,
                'currency' => null,
                'category' => null,
                'error' => 'Connection error. Please try again.',
            ];
        }
    }

    private function getSystemPrompt(): string
    {
        return <<<'PROMPT'
You are a transaction parser. Extract transaction details from natural language input in Serbian or English.

Extract these fields:
- name: What was bought/earned (the item or description)
- amount: The numeric amount (just the number, no currency symbols)
- type: "income" or "expense" (default to "expense" if not specified)
- currency: "RSD", "EUR", or "USD" (default to "RSD" if not specified)
- category: "bills", "food", or "rest" (only for expenses, null for income)

Currency mapping:
- Serbian: "dinara", "dinari", "din", "rsd" → "RSD"
- Serbian: "evra", "eura", "eur" → "EUR"
- Serbian: "dolara", "usd" → "USD"
- English: "dollars" → "USD", "euros" → "EUR"

Category mapping:
- "hrana", "food", "jelo", "groceries", "restaurant", "kafa", "coffee" → "food"
- "racuni", "racun", "bills", "bill", "utilities", "struja", "electricity", "internet", "telefon", "phone", "kirija", "rent" → "bills"
- "rest", "ostalo", "other", anything else → "rest"

Type mapping:
- "prihod", "income", "plata", "salary", "zarada", "earnings" → "income"
- "trosak", "expense", "kupovina", "purchase" → "expense"

Return ONLY valid JSON in this exact format:
{
  "name": "string or null",
  "amount": number or null,
  "type": "income" or "expense" or null,
  "currency": "RSD" or "EUR" or "USD" or null,
  "category": "bills" or "food" or "rest" or null
}

Examples:
- "kafa 250 dinara" → {"name": "kafa", "amount": 250, "type": "expense", "currency": "RSD", "category": "food"}
- "coffee 3 euros" → {"name": "coffee", "amount": 3, "type": "expense", "currency": "EUR", "category": "food"}
- "salary 1500 euros income" → {"name": "salary", "amount": 1500, "type": "income", "currency": "EUR", "category": null}
- "groceries 2500" → {"name": "groceries", "amount": 2500, "type": "expense", "currency": "RSD", "category": "food"}
- "internet bill 30 dollars" → {"name": "internet bill", "amount": 30, "type": "expense", "currency": "USD", "category": "bills"}
PROMPT;
    }
}
