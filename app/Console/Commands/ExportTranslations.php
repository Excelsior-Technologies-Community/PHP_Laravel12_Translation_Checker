<?php

namespace App\Console\Commands;

use Illuminate\Support\Facades\File;
use Illuminate\Console\Command;

class ExportTranslations extends Command
{
    protected $signature = 'translations:export 
                            {--format=csv : Export format (csv, json, excel)}
                            {--locale= : Specific locale to export}
                            {--output= : Output file name}';

    protected $description = 'Export translations to various formats';

    public function handle()
    {
        $format = $this->option('format');
        $locale = $this->option('locale');
        
        $translations = $this->loadTranslations($locale);
        
        $outputFile = $this->option('output') ?? "translations_export_{$format}_" . now()->format('Y-m-d');
        
        switch ($format) {
            case 'csv':
                $this->exportToCsv($translations, $outputFile);
                break;
            case 'json':
                $this->exportToJson($translations, $outputFile);
                break;
            default:
                $this->error("Unsupported format: {$format}");
                return;
        }
        
        $this->info("✓ Exported translations to {$outputFile}.{$format}");
    }

    protected function loadTranslations($locale = null)
    {
        $translations = [];
        $locales = $locale ? [$locale] : array_keys(config('translation-checker.languages'));
        
        foreach ($locales as $lang) {
            $langPath = resource_path("lang/{$lang}");
            if (File::exists($langPath)) {
                $translations[$lang] = $this->loadLanguageFiles($langPath);
            }
        }
        
        return $translations;
    }

    protected function loadLanguageFiles($path)
    {
        $translations = [];
        $files = File::files($path);
        
        foreach ($files as $file) {
            $group = pathinfo($file, PATHINFO_FILENAME);
            $translations[$group] = include $file;
        }
        
        return $translations;
    }

    protected function exportToCsv($translations, $filename)
    {
        $handle = fopen(storage_path("app/{$filename}.csv"), 'w');
        
        fputcsv($handle, ['Locale', 'Group', 'Key', 'Value']);
        
        foreach ($translations as $locale => $groups) {
            foreach ($groups as $group => $keys) {
                $this->flattenAndWriteCsv($handle, $locale, $group, $keys);
            }
        }
        
        fclose($handle);
    }

    protected function flattenAndWriteCsv($handle, $locale, $group, $array, $prefix = '')
    {
        foreach ($array as $key => $value) {
            $fullKey = $prefix ? "{$prefix}.{$key}" : $key;
            if (is_array($value)) {
                $this->flattenAndWriteCsv($handle, $locale, $group, $value, $fullKey);
            } else {
                fputcsv($handle, [$locale, $group, $fullKey, $value]);
            }
        }
    }

    protected function exportToJson($translations, $filename)
    {
        $json = json_encode($translations, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        File::put(storage_path("app/{$filename}.json"), $json);
    }
}