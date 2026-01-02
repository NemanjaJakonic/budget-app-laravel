<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ExchangeRateService;
use Illuminate\Http\JsonResponse;

class ExchangeRateController extends Controller
{
    public function __construct(
        private ExchangeRateService $exchangeRateService
    ) {}

    public function index(): JsonResponse
    {
        $rates = $this->exchangeRateService->getRates();

        return response()->json([
            'rates' => $rates,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    public function refresh(): JsonResponse
    {
        $rates = $this->exchangeRateService->refreshRates();

        return response()->json([
            'rates' => $rates,
            'timestamp' => now()->toIso8601String(),
            'refreshed' => true,
        ]);
    }
}
