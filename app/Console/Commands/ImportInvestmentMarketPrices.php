<?php

namespace App\Console\Commands;

use App\Models\Investment\InvestmentMaster;
use App\Models\Investment\InvestmentMarketPriceHistory;
use App\Services\Investment\InvestmentValuationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ImportInvestmentMarketPrices extends Command
{
    protected $signature = 'investments:import-market-prices {file?} {--date=} {--source=MANUAL}';
    protected $description = 'Import market prices from CSV file or manual entry';

    protected $valuationService;

    public function __construct()
    {
        parent::__construct();
        $this->valuationService = app(\App\Services\Investment\InvestmentValuationService::class);
    }

    public function handle()
    {
        $file = $this->argument('file');
        $priceDate = $this->option('date') ? Carbon::parse($this->option('date')) : Carbon::today();
        $source = $this->option('source');

        if ($file && file_exists($file)) {
            $this->info("Importing prices from CSV file: {$file}");
            $this->importFromCSV($file, $priceDate, $source);
        } else {
            $this->info("No file provided. Use manual import via UI or provide CSV file path.");
            $this->info("CSV format: investment_code,market_price,bid_price,ask_price,yield_rate,volume");
        }

        return 0;
    }

    protected function importFromCSV($file, $priceDate, $source)
    {
        $handle = fopen($file, 'r');
        if (!$handle) {
            $this->error("Could not open file: {$file}");
            return;
        }

        // Skip header row
        $header = fgetcsv($handle);

        $imported = 0;
        $errors = 0;

        while (($row = fgetcsv($handle)) !== false) {
            try {
                if (count($row) < 2) {
                    continue;
                }

                $investmentCode = trim($row[0]);
                $marketPrice = (float) $row[1];
                $bidPrice = isset($row[2]) ? (float) $row[2] : null;
                $askPrice = isset($row[3]) ? (float) $row[3] : null;
                $yieldRate = isset($row[4]) ? (float) $row[4] : null;
                $volume = isset($row[5]) ? (float) $row[5] : null;

                $investment = InvestmentMaster::where('instrument_code', $investmentCode)->first();

                if (!$investment) {
                    $this->warn("Investment not found: {$investmentCode}");
                    $errors++;
                    continue;
                }

                // Use system user
                $systemUser = \App\Models\User::where('company_id', $investment->company_id)
                    ->whereHas('roles', function($q) {
                        $q->whereIn('name', ['admin', 'super_admin']);
                    })
                    ->first() ?? \App\Models\User::where('company_id', $investment->company_id)->first();

                if (!$systemUser) {
                    $this->error("No user found for company {$investment->company_id}");
                    $errors++;
                    continue;
                }

                $priceData = [
                    'price_date' => $priceDate->format('Y-m-d'),
                    'market_price' => $marketPrice,
                    'bid_price' => $bidPrice,
                    'ask_price' => $askPrice,
                    'yield_rate' => $yieldRate,
                    'volume' => $volume,
                    'price_source' => $source,
                ];

                $this->valuationService->storeMarketPrice($investment, $priceData, $systemUser);
                $imported++;
                $this->info("✓ Imported price for {$investmentCode}: {$marketPrice}");

            } catch (\Exception $e) {
                $errors++;
                $this->error("✗ Failed to import row: {$e->getMessage()}");
                Log::error('Market price import failed', [
                    'row' => $row,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        fclose($handle);

        $this->info("\nCompleted: {$imported} imported, {$errors} errors");
    }
}
