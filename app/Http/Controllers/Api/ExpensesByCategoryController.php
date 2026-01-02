<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\User;
use App\Services\ExchangeRateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ExpensesByCategoryController extends Controller
{
    public function __construct(
        private ExchangeRateService $exchangeRateService
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $user = $this->getAuthenticatedUser($request);

        if (!$user) {
            return response()->json([
                'error' => 'Unauthorized. Provide X-API-Key header or log in.',
            ], 401);
        }

        // Validate parameters
        $year = $request->query('year');
        if (!$year) {
            return response()->json(['error' => 'Year parameter is required'], 400);
        }

        $year = (int) $year;
        if ($year < 2000 || $year > 2100) {
            return response()->json(['error' => 'Invalid year parameter'], 400);
        }

        $month = $request->query('month');
        if ($month !== null) {
            $month = (int) $month;
            if ($month < 1 || $month > 12) {
                return response()->json(['error' => 'Invalid month parameter (must be 1-12)'], 400);
            }
        }

        // Get rates
        $rates = $this->exchangeRateService->getRates();

        // Query transactions
        $query = $user->transactions()
            ->where('type', 'expense')
            ->whereYear('date', $year);

        if ($month !== null) {
            $query->whereMonth('date', $month);
        }

        $transactions = $query->get();

        // Initialize category totals
        $categoryTotals = [];
        foreach (Transaction::CATEGORY_LABELS as $value => $label) {
            $categoryTotals[$value] = [
                'category' => $value,
                'label' => $label,
                'totalRSD' => 0,
                'count' => 0,
                'transactions' => [],
            ];
        }
        $categoryTotals['uncategorized'] = [
            'category' => 'uncategorized',
            'label' => 'Uncategorized',
            'totalRSD' => 0,
            'count' => 0,
            'transactions' => [],
        ];

        // Process transactions
        foreach ($transactions as $transaction) {
            $amountInRsd = $transaction->getAmountInRsd($rates);
            $category = $transaction->category ?? 'uncategorized';

            if (!isset($categoryTotals[$category])) {
                $categoryTotals[$category] = [
                    'category' => $category,
                    'label' => $transaction->category_label ?? ucfirst($category),
                    'totalRSD' => 0,
                    'count' => 0,
                    'transactions' => [],
                ];
            }

            $categoryTotals[$category]['totalRSD'] += $amountInRsd;
            $categoryTotals[$category]['count']++;
            $categoryTotals[$category]['transactions'][] = [
                'id' => $transaction->id,
                'name' => $transaction->name,
                'date' => $transaction->date->toDateString(),
                'originalAmount' => (float) $transaction->amount,
                'originalCurrency' => $transaction->currency,
                'amountRSD' => round($amountInRsd, 2),
            ];
        }

        // Round totals
        foreach ($categoryTotals as &$cat) {
            $cat['totalRSD'] = round($cat['totalRSD'], 2);
        }

        // Filter categories with data
        $categoriesWithData = array_values(array_filter($categoryTotals, fn($cat) => $cat['count'] > 0));

        // Sort by total descending
        usort($categoriesWithData, fn($a, $b) => $b['totalRSD'] <=> $a['totalRSD']);

        $grandTotal = array_sum(array_column($categoryTotals, 'totalRSD'));

        return response()->json([
            'period' => [
                'year' => $year,
                'month' => $month,
                'monthName' => $month ? date('F', mktime(0, 0, 0, $month, 1)) : null,
            ],
            'summary' => [
                'totalExpensesRSD' => round($grandTotal, 2),
                'totalTransactions' => $transactions->count(),
                'categoriesCount' => count($categoriesWithData),
            ],
            'exchangeRates' => [
                'EUR_to_RSD' => $rates['RSD'],
                'USD_to_EUR' => $rates['USD'],
            ],
            'categories' => $categoriesWithData,
        ]);
    }

    private function getAuthenticatedUser(Request $request): ?User
    {
        // Check for API key authentication
        $apiKey = $request->header('X-API-Key') ?? str_replace('Bearer ', '', $request->header('Authorization') ?? '');

        if ($apiKey) {
            $configuredKey = config('services.budget_api.key');
            if (!$configuredKey || $apiKey !== $configuredKey) {
                return null;
            }

            $userId = $request->query('user_id') ?? config('services.budget_api.user_id');
            if ($userId) {
                return User::find($userId);
            }

            return null;
        }

        // Fall back to session authentication
        return Auth::user();
    }
}
