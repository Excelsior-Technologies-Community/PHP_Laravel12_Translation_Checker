<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class TranslationScan extends Model
{
    protected $fillable = [
        'locale',
        'missing_count',
        'completion',
        'scanned_at'
    ];
}