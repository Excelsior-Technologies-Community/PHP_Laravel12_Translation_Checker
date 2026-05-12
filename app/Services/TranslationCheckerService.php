<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Cache;

class TranslationCheckerService
{
    protected $locales = ['en', 'gu', 'hi', 'fr', 'es', 'de', 'zh', 'ja', 'ko', 'ru'];
    
    /**
     * Get all translation keys from English files
     */
    public function getAllTranslationKeys()
    {
        $cacheKey = 'translations.all_keys';
        
        return Cache::remember($cacheKey, 3600, function () {
            $keys = [];
            $enPath = lang_path('en');
            
            if (!File::exists($enPath)) {
                return $keys;
            }
            
            $files = File::files($enPath);
            foreach ($files as $file) {
                $translations = require $file->getPathname();
                $this->extractKeysRecursive($translations, $keys, pathinfo($file->getFilename(), PATHINFO_FILENAME));
            }
            
            return $keys;
        });
    }
    
    /**
     * Get missing translations for a specific locale
     */
    public function getMissingTranslations($locale)
    {
        $cacheKey = "translations.missing.{$locale}";
        
        return Cache::remember($cacheKey, 3600, function () use ($locale) {
            if ($locale === 'en') {
                return [];
            }
            
            $allKeys = $this->getAllTranslationKeys();
            $existingTranslations = $this->loadTranslations($locale);
            $missing = [];
            
            foreach ($allKeys as $key) {
                if (!$this->hasTranslation($existingTranslations, $key)) {
                    $missing[] = [
                        'key' => $key,
                        'suggested_value' => $this->getSuggestedValue($key)
                    ];
                }
            }
            
            return $missing;
        });
    }
    
    /**
     * Get translation statistics for all locales
     */
    public function getTranslationStats()
    {
        $stats = [];
        $allKeys = $this->getAllTranslationKeys();
        $totalKeys = count($allKeys);
        
        foreach ($this->locales as $locale) {
            if ($locale === 'en') {
                $stats[$locale] = [
                    'completion' => 100,
                    'missing_count' => 0,
                    'total_keys' => $totalKeys
                ];
            } else {
                $missing = $this->getMissingTranslations($locale);
                $missingCount = count($missing);
                $completion = $totalKeys > 0 ? round((($totalKeys - $missingCount) / $totalKeys) * 100, 2) : 100;
                
                $stats[$locale] = [
                    'completion' => $completion,
                    'missing_count' => $missingCount,
                    'total_keys' => $totalKeys
                ];
            }
        }
        
        return $stats;
    }
    
    /**
     * Load all translations for a specific locale
     */
    protected function loadTranslations($locale)
    {
        $langPath = lang_path($locale);
        $translations = [];
        
        if (!File::exists($langPath)) {
            return $translations;
        }
        
        $files = File::files($langPath);
        foreach ($files as $file) {
            $fileKey = pathinfo($file->getFilename(), PATHINFO_FILENAME);
            $translations[$fileKey] = require $file->getPathname();
        }
        
        return $translations;
    }
    
    /**
     * Check if translation exists for a key
     */
    protected function hasTranslation($translations, $key)
    {
        $parts = explode('.', $key);
        $file = $parts[0];
        $keyPath = array_slice($parts, 1);
        
        if (!isset($translations[$file])) {
            return false;
        }
        
        $current = $translations[$file];
        foreach ($keyPath as $segment) {
            if (!is_array($current) || !isset($current[$segment])) {
                return false;
            }
            $current = $current[$segment];
        }
        
        return !empty($current);
    }
    
    /**
     * Get suggested value for a key
     */
    protected function getSuggestedValue($key)
    {
        $parts = explode('.', $key);
        return end($parts);
    }
    
    /**
     * Extract keys recursively from array
     */
    protected function extractKeysRecursive($array, &$keys, $prefix = '')
    {
        foreach ($array as $key => $value) {
            $fullKey = $prefix ? $prefix . '.' . $key : $key;
            if (is_array($value)) {
                $this->extractKeysRecursive($value, $keys, $fullKey);
            } else {
                $keys[] = $fullKey;
            }
        }
    }
    
    /**
     * Clear all translation caches
     */
    public function clearCache()
    {
        Cache::forget('translations.all_keys');
        foreach ($this->locales as $locale) {
            Cache::forget("translations.missing.{$locale}");
        }
    }
}