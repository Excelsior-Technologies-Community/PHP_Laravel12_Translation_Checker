<?php

namespace App\Http\Controllers;

use App\Services\TranslationCheckerService;
use Illuminate\Http\Request;

class TranslationController extends Controller
{
    protected $checkerService;
    
    public function __construct(TranslationCheckerService $checkerService)
    {
        $this->checkerService = $checkerService;
    }
    
    // Show simple form
    public function checkForm()
    {
        return view('translations.check');
    }
    
    // Check missing translations
    public function check(Request $request)
    {
        $locale = $request->get('locale', 'gu');
        $format = $request->get('format', 'html');
        
        $missing = $this->checkerService->getMissingTranslations($locale);
        $stats = $this->checkerService->getTranslationStats();
        $completion = $stats[$locale]['completion'] ?? 0;
        
        if ($format === 'json') {
            return response()->json([
                'locale' => $locale,
                'missing_count' => count($missing),
                'completion' => $completion,
                'missing_translations' => $missing
            ]);
        }
        
        return view('translations.check', compact('locale', 'missing', 'completion'));
    }
    
    // Export to CSV
    public function export(Request $request)
    {
        $locale = $request->get('locale', 'gu');
        $missing = $this->checkerService->getMissingTranslations($locale);
        
        $csvFileName = "missing_translations_{$locale}_" . date('Y-m-d_His') . ".csv";
        $handle = fopen('php://temp', 'w');
        
        // Add headers
        fputcsv($handle, ['Key', 'Suggested Value', 'Status']);
        
        // Add data
        foreach ($missing as $item) {
            fputcsv($handle, [$item['key'], $item['suggested_value'], 'Missing']);
        }
        
        rewind($handle);
        $csvContent = stream_get_contents($handle);
        fclose($handle);
        
        return response($csvContent, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename={$csvFileName}",
        ]);
    }
}