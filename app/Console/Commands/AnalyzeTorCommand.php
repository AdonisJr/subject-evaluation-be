<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\UploadedTor;
use Illuminate\Support\Facades\Log;
use Throwable;
use App\Http\Controllers\TesseractOcrController;

class AnalyzeTorCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * Usage: php artisan tor:analyze {torId} {curriculumId}
     */
    protected $signature = 'tor:analyze {torId} {curriculumId}';

    /**
     * The console command description.
     */
    protected $description = 'Analyze a TOR and match it with the given curriculum.';

    /**
     * Execute the console command.
     */

    public function handle()
    {
        $torId = $this->argument('torId');
        $curriculumId = $this->argument('curriculumId');

        Log::info("🧩 Starting TOR analysis for TOR ID: {$torId}, Curriculum ID: {$curriculumId}");

        try {
            $controller = new TesseractOcrController();
            $response = $controller->analyzeTor($torId, $curriculumId);

            Log::info("✅ TOR analysis completed via OCR Controller for ID {$torId}");

            return 0;
        } catch (\Throwable $e) {
            Log::error("🔥 Error analyzing TOR ID {$torId}: " . $e->getMessage());
            $this->error("Error: {$e->getMessage()}");
            return 1;
        }
    }
}
