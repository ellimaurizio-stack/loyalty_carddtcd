<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\RfmService;

class CalculateRfmCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'calculate:rfm {--brand_id=} {--store_id=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calculates RFM scores and segments for all customers';

    /**
     * Execute the console command.
     */
    public function handle(RfmService $rfmService)
    {
        $this->info('Starting RFM Calculation...');
        
        $brandId = $this->option('brand_id');
        $storeId = $this->option('store_id');

        $rfmService->calculateRfm($brandId, $storeId);

        $this->info('RFM Calculation Completed Successfully!');
    }
}
