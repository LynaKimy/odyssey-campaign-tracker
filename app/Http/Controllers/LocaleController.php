<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;

class LocaleController extends Controller
{
    public function __invoke(string $locale): RedirectResponse
    {
        if (! in_array($locale, ['en', 'fr'], true)) {
            abort(400);
        }

        session(['locale' => $locale]);

        if ($user = auth()->user()) {
            $user->update(['locale' => $locale]);
        }

        return redirect()->back();
    }
}
