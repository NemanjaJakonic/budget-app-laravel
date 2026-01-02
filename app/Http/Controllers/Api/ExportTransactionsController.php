<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ExchangeRateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportTransactionsController extends Controller
{
    public function __construct(
        private ExchangeRateService $exchangeRateService
    ) {}

    public function __invoke(Request $request): StreamedResponse
    {
        $user = Auth::user();
        $transactions = $user->transactions()->orderBy('date', 'desc')->get();
        $rates = $this->exchangeRateService->getRates();

        // Group transactions by month
        $transactionsByMonth = $transactions->groupBy(function ($transaction) {
            return $transaction->date->format('Y-n'); // Year-Month without leading zero
        });

        $spreadsheet = new Spreadsheet();
        $spreadsheet->removeSheetByIndex(0);

        foreach ($transactionsByMonth as $monthKey => $monthTransactions) {
            [$year, $month] = explode('-', $monthKey);
            $monthName = date('F', mktime(0, 0, 0, (int) $month, 1));
            $sheetName = "{$monthName} {$year}";

            $sheet = $spreadsheet->createSheet();
            $sheet->setTitle(substr($sheetName, 0, 31)); // Excel sheet names max 31 chars

            // Headers
            $sheet->setCellValue('A1', 'Name');
            $sheet->setCellValue('B1', 'Date');
            $sheet->setCellValue('C1', 'Category');
            $sheet->setCellValue('D1', 'Amount (RSD)');

            // Style headers
            $sheet->getStyle('A1:D1')->getFont()->setBold(true);

            $row = 2;
            foreach ($monthTransactions as $transaction) {
                // Convert amount to RSD
                $amountInRsd = $transaction->getAmountInRsd($rates);

                // Add minus sign for expenses
                if ($transaction->type === 'expense') {
                    $amountInRsd = -$amountInRsd;
                }

                $sheet->setCellValue("A{$row}", $transaction->name);
                $sheet->setCellValue("B{$row}", $transaction->date->format('d/m/Y'));
                $sheet->setCellValue("C{$row}", $transaction->category_label ?? '');
                $sheet->setCellValue("D{$row}", $amountInRsd);

                $row++;
            }

            // Add total row
            $lastDataRow = $row - 1;
            $sheet->setCellValue("A{$row}", 'TOTAL');
            $sheet->setCellValue("D{$row}", "=SUM(D2:D{$lastDataRow})");
            $sheet->getStyle("A{$row}:D{$row}")->getFont()->setBold(true);

            // Auto-size columns
            foreach (range('A', 'D') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            // Format amount column as number
            $sheet->getStyle("D2:D{$row}")->getNumberFormat()->setFormatCode('#,##0.00 "RSD"');
        }

        // Set first sheet as active
        if ($spreadsheet->getSheetCount() > 0) {
            $spreadsheet->setActiveSheetIndex(0);
        }

        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, 'transactions.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
