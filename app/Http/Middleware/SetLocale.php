<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;

class SetLocale
{
    public function handle(Request $request, Closure $next)
    {
        $locale = $request->get('lang', Session::get('locale', config('app.locale')));
        
        if (in_array($locale, array_keys(config('translation-checker.languages')))) {
            App::setLocale($locale);
            Session::put('locale', $locale);
        }
        
        return $next($request);
    }
}