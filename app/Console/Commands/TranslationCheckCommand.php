<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class TranslationCheckCommand extends Command
{
    protected $signature = 'translations:check 
                            {--directory=resources/lang} 
                            {--report=cli}'; // cli or html

    protected $description = 'Check missing translation files and keys';

    private $errors = [
        'files' => [],
        'keys' => [],
    ];

    public function handle()
    {
        $langPath = base_path($this->option('directory'));

        if (!File::exists($langPath)) {
            $this->error("Directory not found: $langPath");
            return;
        }

        $languages = File::directories($langPath);
        $langs = array_map(fn($p) => basename($p), $languages);

        if (empty($langs)) {
            $this->error("No languages found in $langPath");
            return;
        }

        $referenceLang = $languages[0];
        $files = File::files($referenceLang);

        foreach ($langs as $lang) {
            $this->line("\nChecking Language: $lang");
            $this->line(str_repeat('-', 25));

            foreach ($files as $file) {
                $fileName = $file->getFilename();
                $filePath = "$langPath/$lang/$fileName";

                // Check file exists
                if (!File::exists($filePath)) {
                    $this->warn("Missing File: $lang/$fileName");
                    $this->errors['files'][] = "$lang/$fileName";
                    continue;
                }

                $referenceData = include $file->getPathname();
                $langData = include $filePath;

                if (!is_array($referenceData) || !is_array($langData)) {
                    continue;
                }

                $this->checkKeys($referenceData, $langData, $lang, $fileName);
            }
        }

        $this->line("\n------------------------");
        $this->line("Summary:");
        $this->line("Languages checked: " . count($langs));
        $this->line("Missing files: " . count($this->errors['files']));
        $this->line("Missing keys: " . count($this->errors['keys']));
        $this->line("------------------------\n");

        if ($this->option('report') === 'html') {
            $this->generateHtmlReport();
        }
    }

    private function checkKeys($reference, $target, $lang, $fileName, $prefix = '')
    {
        if (!is_array($reference) || !is_array($target))
            return;

        foreach ($reference as $key => $value) {
            $fullKey = $prefix ? "$prefix.$key" : $key;

            if (!array_key_exists($key, $target)) {
                $this->error("Missing Key: $lang.$fileName.$fullKey");
                $this->errors['keys'][] = "$lang.$fileName.$fullKey";
            } elseif (is_array($value)) {
                $this->checkKeys($value, $target[$key], $lang, $fileName, $fullKey);
            }
        }
    }

    private function generateHtmlReport()
    {
        $html = '<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Translation Checker Report</title>
<style>
body { font-family: Arial, sans-serif; background:#0f172a; color:#fff; padding:20px; }
h1 { color:#facc15; }
.language { margin-top:20px; }
.file { margin-left:20px; margin-top:10px; }
.card { background:#1e293b; padding:10px; border-radius:8px; margin:5px 0; }
.error { color:#f87171; }
.warn { color:#facc15; }
.success { color:#4ade80; }
summary { cursor:pointer; font-weight:bold; }
</style>
</head>
<body>
<h1>Translation Checker Report</h1>';

        $languages = [];

        foreach ($this->errors['files'] as $file) {
            [$lang, $fileName] = explode('/', $file, 2);
            $languages[$lang]['files'][] = $fileName;
        }

        foreach ($this->errors['keys'] as $key) {
            [$lang, $rest] = explode('.', $key, 2);
            $languages[$lang]['keys'][] = $rest;
        }

        if (empty($languages)) {
            $html .= '<div class="card success">No missing files or keys found! ✅</div>';
        } else {
            foreach ($languages as $lang => $data) {
                $html .= '<div class="language"><h2>Language: ' . $lang . '</h2>';

                if (!empty($data['files'])) {
                    $html .= '<details open><summary>Missing Files (' . count($data['files']) . ')</summary>';
                    foreach ($data['files'] as $file) {
                        $html .= '<div class="card warn">' . $file . '</div>';
                    }
                    $html .= '</details>';
                }

                if (!empty($data['keys'])) {
                    $html .= '<details open><summary>Missing Keys (' . count($data['keys']) . ')</summary>';
                    foreach ($data['keys'] as $key) {
                        $html .= '<div class="card error">' . $key . '</div>';
                    }
                    $html .= '</details>';
                }

                $html .= '</div>';
            }
        }

        $html .= '</body></html>';

        $file = base_path('translation_report.html');
        file_put_contents($file, $html);

        $this->info("\nHTML report generated at: $file");
    }
}