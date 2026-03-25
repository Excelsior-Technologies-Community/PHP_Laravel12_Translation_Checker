# PHP_Laravel12_Translation_Checker


A Laravel 12 project designed to check missing translation files and keys in multilingual applications.
It provides both CLI output and a dark-themed HTML report, making it easy to maintain translations for multiple languages.



## Project Description

Maintaining translations in Laravel applications can be challenging, especially when working with multiple languages.

This project helps developers to:

- Identify missing translation files in different languages.
- Identify missing translation keys within existing files.
- Generate a detailed summary in CLI or HTML report.
- Quickly ensure translations are consistent across all supported languages.

This tool is particularly useful in multilingual Laravel applications where new translations are frequently added.



## Features

- Check for missing translation files per language.
- Check for missing keys within existing translation files.
- CLI output with summary.
- Dark-themed HTML report for visual inspection.
- Supports custom directories for language files.
- Lightweight, no additional database setup required.
- Easy to extend for additional languages.



## Technologies Used

- Laravel 12 – PHP framework.
- PHP 8.1+ – Core programming language.
- Composer – Dependency management.
- Blade & HTML/CSS – For HTML report generation.
- Laravel Artisan Commands – For CLI execution.



---


## Installation Steps


---


## STEP 1: Create Laravel 12 Project

### Open terminal / CMD and run:

```
composer create-project laravel/laravel PHP_Laravel12_Translation_Checker "12.*"

```

### Go inside project:

```
cd PHP_Laravel12_Translation_Checker

```

#### Explanation:

Installs a fresh Laravel 12 application and moves into the project folder.




## STEP 2: Database Setup (Optional)

### Update database details:

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=laravel12_Translation_Checker
DB_USERNAME=root
DB_PASSWORD=

```

### Create database in MySQL / phpMyAdmin:

```
Database name: laravel12_Translation_Checker

```

### Then Run:

```
php artisan migrate

```


#### Explanation:

Connects Laravel to MySQL and creates default tables; optional for this project since translation checker doesn't need a database.




## STEP 3: Install the package

### Run:

```
composer require --dev larswiegers/laravel-translations-checker

```

#### Explanation:

Installs the official Laravel translations checker package for development use.




## STEP 4: Create Sample Language Files

### Create:

```
├── resources/
│   └── lang/
│       ├── en/
│       │   ├── auth.php
│       │   └── messages.php
│       └── fr/
│           └── auth.php

```


### resources/lang/en/auth.php

```
<?php
return [
    'failed' => 'Login failed',
    'password' => 'Wrong password',
];

```



### resources/lang/en/messages.php

```
<?php
return [
    'welcome' => 'Welcome',
    'bye' => 'Goodbye',
];

```

### resources/lang/fr/auth.php

```
<?php
return [
    'failed' => 'Échec de connexion',
];

```

#### Explanation:

Sets up English and French translations with some missing keys/files for testing.




## STEP 5: Create Artisan Command

### Run: 

```
php artisan make:command TranslationCheckCommand

```


### app/Console/Commands/TranslationCheckCommand.php

```
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

```

#### Explanation:

Creates a custom CLI command where we will implement the translation checker logic.




## STEP 6: Run Command

### CLI output:

```
php artisan translations:check

```

#### Expected Output:


<img src="screenshots/Screenshot 2026-03-25 131335.png" width="900">


#### Explanation:

Runs the checker in the terminal and shows missing files and keys.




### HTML report:

```
php artisan translations:check --report=html

```

#### Expected Output:


<img src="screenshots/Screenshot 2026-03-25 131358.png" width="900">


#### Explanation:

Generates a dark-themed HTML report with missing files and keys.



### Custom Directory:

```
php artisan translations:check --directory=resources/lang

```

#### Expected Output:


<img src="screenshots/Screenshot 2026-03-25 132412.png" width="900">


#### Explanation:

Allows checking translations in a custom folder instead of default resources/lang.



---


## Project Folder Structure:

```
PHP_Laravel12_Translation_Checker/
│
├── app/
│   └── Console/
│       └── Commands/
│           └── TranslationCheckCommand.php   <-- Custom CLI command
│
├── resources/
│   └── lang/
│       ├── en/
│       │   ├── auth.php
│       │   └── messages.php
│       └── fr/
│           └── auth.php
│
├── vendor/                                 <-- Composer packages
│
├── artisan                                  <-- Laravel CLI
├── composer.json
├── composer.lock
├── .env                                     <-- Environment config
├── README.md
└── translation_report.html                  <-- Generated HTML report

```
