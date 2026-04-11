<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolve and set the application locale per request
 *
 * @description Priority: authenticated user preference > browser Accept-Language > config default.
 * Registered as global web middleware in bootstrap/app.php.
 */
class SetLocale
{
    /** @var list<string> */
    private const SUPPORTED_LOCALES = ['en', 'fr'];

    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->resolveLocale($request);
        app()->setLocale($locale);

        return $next($request);
    }

    private function resolveLocale(Request $request): string
    {
        if ($user = $request->user()) {
            return in_array($user->locale, self::SUPPORTED_LOCALES, true)
                ? $user->locale
                : config('app.locale');
        }

        // Guest: check session first (set by locale switcher), then browser
        if ($sessionLocale = session('locale')) {
            return in_array($sessionLocale, self::SUPPORTED_LOCALES, true)
                ? $sessionLocale
                : config('app.locale');
        }

        return $request->getPreferredLanguage(self::SUPPORTED_LOCALES)
            ?? config('app.locale');
    }
}
