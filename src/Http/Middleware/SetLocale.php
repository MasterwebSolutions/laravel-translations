<?php

namespace Masterweb\Translations\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Masterweb\Translations\TranslationManager;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $validLocales = $this->getValidLocales();
        $locale = $request->route('locale');
        $cookieName = config('translations.locale_cookie', 'lang');
        $cookieDays = config('translations.locale_cookie_days', 365);

        if ($locale && in_array($locale, $validLocales)) {
            App::setLocale($locale);
            cookie()->queue($cookieName, $locale, 60 * 24 * $cookieDays);
        } elseif ($locale) {
            $defaultLang = $validLocales[0] ?? 'es';
            return redirect('/' . $defaultLang, 302);
        } elseif ($cookie = $request->cookie($cookieName)) {
            if (in_array($cookie, $validLocales)) {
                App::setLocale($cookie);
            }
        }

        return $next($request);
    }

    private function getValidLocales(): array
    {
        try {
            return TranslationManager::getLanguages();
        } catch (\Throwable $e) {
            return config('translations.available_languages', ['es', 'en']);
        }
    }
}
