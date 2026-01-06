<?php

namespace App\Console\Commands;

use App\Models\Transaction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ImportTransactionsFromCsv extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'transactions:import-csv {file : Path to the CSV file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import transactions from a CSV file';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $filePath = $this->argument('file');

        if (! file_exists($filePath)) {
            $this->error("File not found: {$filePath}");

            return Command::FAILURE;
        }

        if (! is_readable($filePath)) {
            $this->error("File is not readable: {$filePath}");

            return Command::FAILURE;
        }

        $this->info("Reading CSV file: {$filePath}");

        $handle = fopen($filePath, 'r');
        if ($handle === false) {
            $this->error("Could not open file: {$filePath}");

            return Command::FAILURE;
        }

        // Read header row
        $header = fgetcsv($handle);
        if ($header === false) {
            $this->error('Could not read CSV header');

            return Command::FAILURE;
        }
    

        // Normalize header (trim and lowercase)
        $header = array_map(fn ($col) => strtolower(trim($col)), $header);

        $expectedColumns = ['name', 'amount', 'date', 'user_id', 'type', 'currency', 'category'];
        $missingColumns = array_diff($expectedColumns, $header);

        if (! empty($missingColumns)) {
            $this->error('Missing required columns: '.implode(', ', $missingColumns));
            $this->info('Found columns: '.implode(', ', $header));

            return Command::FAILURE;
        }

        $this->info('CSV header validated successfully');
        $this->newLine();

        $imported = 0;
        $skipped = 0;
        $errors = [];
        $rowNumber = 1;

        DB::beginTransaction();

        try {
            while (($row = fgetcsv($handle)) !== false) {
                $rowNumber++;

                if (count($row) !== count($header)) {
                    $errors[] = "Row {$rowNumber}: Column count mismatch";
                    $skipped++;

                    continue;
                }

                // Map row data to associative array
                $data = array_combine($header, $row);

                // Validate row data
                $validator = Validator::make($data, [
                    'name' => ['required', 'string', 'max:255'],
                    'amount' => ['required', 'numeric', 'min:0'],
                    'date' => ['required', 'date'],
                    'user_id' => ['required', 'integer', 'exists:users,id'],
                    'type' => ['required', 'in:income,expense'],
                    'currency' => ['required', 'in:RSD,EUR,USD'],
                    'category' => ['nullable', 'in:bills,food,rest'],
                ]);

                if ($validator->fails()) {
                    $errors[] = "Row {$rowNumber}: ".implode(', ', $validator->errors()->all());
                    $skipped++;

                    continue;
                }

                // Create transaction
                Transaction::create([
                    'user_id' => (int) $data['user_id'],
                    'name' => trim($data['name']),
                    'amount' => (float) $data['amount'],
                    'type' => trim($data['type']),
                    'currency' => trim($data['currency']),
                    'date' => $data['date'],
                    'category' => ! empty($data['category']) ? trim($data['category']) : null,
                ]);

                $imported++;
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            $this->newLine(2);
            $this->error("Error importing transactions: {$e->getMessage()}");

            return Command::FAILURE;
        } finally {
            fclose($handle);
        }

        $this->newLine(2);
        $this->info('Import completed!');
        $this->table(
            ['Status', 'Count'],
            [
                ['Imported', $imported],
                ['Skipped', $skipped],
                ['Total Processed', $imported + $skipped],
            ]
        );

        if (! empty($errors)) {
            $this->newLine();
            $this->warn('Errors encountered:');
            foreach (array_slice($errors, 0, 10) as $error) {
                $this->line("  - {$error}");
            }
            if (count($errors) > 10) {
                $this->line('  ... and '.(count($errors) - 10).' more errors');
            }
        }

        return Command::SUCCESS;
    }
}
