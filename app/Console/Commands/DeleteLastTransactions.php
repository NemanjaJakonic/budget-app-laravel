<?php

namespace App\Console\Commands;

use App\Models\Transaction;
use Illuminate\Console\Command;

class DeleteLastTransactions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'transactions:delete-last {count : Number of transactions to delete} {--force : Skip confirmation prompt}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete the last N transactions (ordered by ID descending)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $count = (int) $this->argument('count');

        if ($count <= 0) {
            $this->error('Count must be a positive integer');

            return Command::FAILURE;
        }

        $totalTransactions = Transaction::count();

        if ($count > $totalTransactions) {
            $this->warn("Only {$totalTransactions} transactions exist. Deleting all of them.");
            $count = $totalTransactions;
        }

        if (! $this->option('force')) {
            if (! $this->confirm("Are you sure you want to delete the last {$count} transactions?")) {
                $this->info('Operation cancelled.');

                return Command::SUCCESS;
            }
        }

        $this->info("Deleting the last {$count} transactions...");

        $deleted = Transaction::orderBy('id', 'desc')
            ->limit($count)
            ->delete();

        $this->info("Successfully deleted {$deleted} transaction(s).");

        return Command::SUCCESS;
    }
}
