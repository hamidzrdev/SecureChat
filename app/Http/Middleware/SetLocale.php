<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * @var array<int, string>
     */
    private array $supportedLocales = ['en', 'fa'];

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $sessionLocale = $request->session()->get('app.locale');
        $resolvedLocale = is_string($sessionLocale) && in_array($sessionLocale, $this->supportedLocales, true)
            ? $sessionLocale
            : $request->getPreferredLanguage($this->supportedLocales);

        if (! is_string($resolvedLocale) || ! in_array($resolvedLocale, $this->supportedLocales, true)) {
            $resolvedLocale = config('app.locale', 'en');
        }

        app()->setLocale($resolvedLocale);
        $request->session()->put('app.locale', $resolvedLocale);

        return $next($request);
    }
}
