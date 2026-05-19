<?php

namespace App\Services;

use App\Models\TranslationScan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

class TranslationCheckerService
{
    protected $locales = [
        'en',
        'gu',
        'hi',
        'fr',
        'es',
        'de',
        'zh',
        'ja',
        'ko',
        'ru'
    ];

    /**
     * Get all translation keys from English files
     */
    public function getAllTranslationKeys()
    {
        return Cache::remember('translations.all_keys', 3600, function () {

            $keys = [];
            $enPath = lang_path('en');

            if (!File::exists($enPath)) {
                return $keys;
            }

            $files = File::files($enPath);

            foreach ($files as $file) {
                $translations = require $file->getPathname();

                $this->extractKeysRecursive(
                    $translations,
                    $keys,
                    pathinfo($file->getFilename(), PATHINFO_FILENAME)
                );
            }

            return $keys;
        });
    }

    /**
     * Get missing translations for locale
     */
    public function getMissingTranslations($locale)
    {
        return Cache::remember(
            "translations.missing.$locale",
            3600,
            function () use ($locale) {

                if ($locale === 'en') {
                    return [];
                }

                $allKeys = $this->getAllTranslationKeys();

                $existingTranslations =
                    $this->loadTranslations($locale);

                $missing = [];

                foreach ($allKeys as $key) {

                    if (
                        !$this->hasTranslation(
                            $existingTranslations,
                            $key
                        )
                    ) {
                        $missing[] = [
                            'key' => $key,
                            'suggested_value' => $this->getSuggestedValue($key)
                        ];
                    }
                }

                return $missing;
            }
        );
    }

    /**
     * Translation statistics
     */
    public function getTranslationStats()
    {
        $stats = [];

        $allKeys =
            $this->getAllTranslationKeys();

        $totalKeys =
            count($allKeys);

        foreach ($this->locales as $locale) {

            if ($locale === 'en') {

                $stats[$locale] = [
                    'completion' => 100,
                    'missing_count' => 0,
                    'total_keys' => $totalKeys
                ];

                continue;
            }

            $missing =
                $this->getMissingTranslations(
                    $locale
                );

            $missingCount =
                count($missing);

            $completion =
                $totalKeys > 0
                ?
                round(
                    (
                        (
                            $totalKeys -
                            $missingCount
                        )
                        /
                        $totalKeys
                    ) * 100,
                    2
                )
                :
                100;

            $stats[$locale] = [
                'completion' => $completion,
                'missing_count' => $missingCount,
                'total_keys' => $totalKeys
            ];
        }

        return $stats;
    }

    /**
     * Save scan history
     */



    public function saveScanHistory(
        $locale,
        $missingCount,
        $completion
    ) {
        TranslationScan::create([
            'locale' => $locale,
            'missing_count' => $missingCount,
            'completion' => $completion,
            'scanned_at' => now()
        ]);
    }

    /**
     * Load translations
     */
    protected function loadTranslations($locale)
    {
        $translations = [];

        $langPath = lang_path($locale);

        if (!File::exists($langPath)) {
            return $translations;
        }

        foreach (File::files($langPath) as $file) {

            $fileKey = pathinfo(
                $file->getFilename(),
                PATHINFO_FILENAME
            );

            $translations[$fileKey] =
                require $file->getPathname();
        }

        return $translations;
    }

    /**
     * Check translation exists
     */
    protected function hasTranslation(
        $translations,
        $key
    ) {
        $parts = explode('.', $key);

        $file = $parts[0];

        $keyPath = array_slice($parts, 1);

        if (!isset($translations[$file])) {
            return false;
        }

        $current = $translations[$file];

        foreach ($keyPath as $segment) {

            if (
                !is_array($current)
                || !isset($current[$segment])
            ) {
                return false;
            }

            $current = $current[$segment];
        }

        return !empty($current);
    }

    /**
     * Suggested value
     */
    protected function getSuggestedValue($key)
    {
        $parts = explode('.', $key);

        return end($parts);
    }

    /**
     * Recursive extraction
     */
    protected function extractKeysRecursive(
        $array,
        &$keys,
        $prefix = ''
    ) {
        foreach ($array as $key => $value) {

            $fullKey = $prefix
                ? $prefix . '.' . $key
                : $key;

            if (is_array($value)) {

                $this->extractKeysRecursive(
                    $value,
                    $keys,
                    $fullKey
                );
            } else {
                $keys[] = $fullKey;
            }
        }
    }

    /**
     * Clear cache
     */
    public function clearCache()
    {
        Cache::forget('translations.all_keys');

        foreach ($this->locales as $locale) {
            Cache::forget(
                "translations.missing.$locale"
            );
        }
    }
}
