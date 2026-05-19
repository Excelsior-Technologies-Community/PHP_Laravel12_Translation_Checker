<?php

namespace App\Console\Commands;

use App\Services\TranslationCheckerService;
use Illuminate\Console\Command;

class CheckMissingTranslations extends Command
{
    protected $signature = 'translations:check {--locale= : Check specific locale only} 
                                      {--report : Generate HTML report}
                                      {--fix : Auto-fill missing translations from default locale}';
    
    protected $description = 'Check for missing translations across all language files';

    protected $checkerService;

    public function __construct(TranslationCheckerService $checkerService)
    {
        parent::__construct();
        $this->checkerService = $checkerService;
    }

    public function handle()
    {
        $this->info('🔍 Starting translation check...');

        $locale = $this->option('locale');
        $generateReport = $this->option('report');
        $autoFix = $this->option('fix');

        $results = $this->checkerService->checkMissingTranslations($locale);

        $this->displayResults($results);

        if ($autoFix && !$locale) {
            $this->warn('Auto-fix requires a specific locale. Use --locale parameter.');
        } elseif ($autoFix && $locale) {
            $this->info('🔄 Auto-filling missing translations...');
            $filled = $this->checkerService->autoFillMissing($locale);
            $this->info("✓ Added {$filled} missing translations.");
        }

        if ($generateReport) {
            $reportPath = $this->checkerService->generateReport($results);
            $this->info("📊 Report generated: {$reportPath}");
        }

        $totalMissing = array_sum(array_map('count', $results));
        
        if ($totalMissing === 0) {
            $this->info('✅ All translations are complete!');
        } else {
            $this->warn("⚠️ Found {$totalMissing} missing translations.");
        }
    }

    protected function displayResults($results)
    {
        $this->newLine();
        $this->table(
            ['Locale', 'Missing Count', 'Status'],
            array_map(function ($locale, $missing) {
                return [
                    $locale,
                    count($missing),
                    count($missing) === 0 ? '✓ Complete' : '⚠️ Missing'
                ];
            }, array_keys($results), $results)
        );

        foreach ($results as $locale => $missing) {
            if (!empty($missing) && !$this->option('report')) {
                $this->newLine();
                $this->info("Missing translations for {$locale}:");
                foreach (array_slice($missing, 0, 10) as $item) {
                    $this->line("  • {$item['key']} → Suggested: {$item['suggested_value']}");
                }
                if (count($missing) > 10) {
                    $this->line("  ... and " . (count($missing) - 10) . " more");
                }
            }
        }
    }
}