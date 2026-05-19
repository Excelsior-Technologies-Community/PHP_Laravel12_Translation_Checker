<?php

namespace App\Http\Controllers;

use App\Models\TranslationScan;
use App\Services\TranslationCheckerService;
use Illuminate\Http\Request;

class TranslationController extends Controller
{
    protected $checkerService;

    public function __construct(
        TranslationCheckerService $checkerService
    ) {
        $this->checkerService = $checkerService;
    }

    public function checkForm()
    {
        return view('translations.check');
    }

    public function check(Request $request)
    {
        $locale = $request->get('locale', 'gu');
        $format = $request->get('format', 'html');

        $missing =
            $this->checkerService
            ->getMissingTranslations($locale);

        $stats =
            $this->checkerService
            ->getTranslationStats();

        $completion =
            $stats[$locale]['completion'] ?? 0;

        // Save only selected locale
        $this->checkerService
            ->saveScanHistory(
                $locale,
                count($missing),
                $completion
            );

        if ($format === 'json') {

            return response()->json([
                'locale' => $locale,
                'missing_count' => count($missing),
                'completion' => $completion,
                'missing_translations' => $missing
            ]);
        }

        return view(
            'translations.check',
            compact(
                'locale',
                'missing',
                'completion'
            )
        );
    }

    public function export(Request $request)
    {
        $locale =
            $request->get('locale', 'gu');

        $missing =
            $this->checkerService
            ->getMissingTranslations($locale);

        $filename =
            "missing_translations_{$locale}_"
            . now()->format('Y-m-d_His')
            . ".csv";

        $handle =
            fopen('php://temp', 'w');

        fputcsv(
            $handle,
            ['Key', 'Suggested Value', 'Status']
        );

        foreach ($missing as $item) {

            fputcsv(
                $handle,
                [
                    $item['key'],
                    $item['suggested_value'],
                    'Missing'
                ]
            );
        }

        rewind($handle);

        $csv =
            stream_get_contents($handle);

        fclose($handle);

        return response(
            $csv,
            200,
            [
                'Content-Type' => 'text/csv',
                'Content-Disposition' =>
                "attachment; filename=$filename"
            ]
        );
    }

    public function history()
    {
        $history =
            TranslationScan::latest()
            ->paginate(10);

        return view(
            'translations.history',
            compact('history')
        );
    }

    public function clearCache()
    {
        $this->checkerService
            ->clearCache();

        return response()->json([
            'message' =>
            'Cache cleared successfully'
        ]);
    }
}
    