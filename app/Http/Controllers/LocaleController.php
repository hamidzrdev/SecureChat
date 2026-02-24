<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Rule;

class LocaleController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'locale' => ['required', 'string', Rule::in(['en', 'fa'])],
            'redirect_to' => ['nullable', 'string'],
        ]);

        $locale = (string) $validated['locale'];
        $request->session()->put('app.locale', $locale);
        app()->setLocale($locale);

        $redirectTo = (string) ($validated['redirect_to'] ?? '');

        if ($redirectTo !== '' && str_starts_with($redirectTo, url('/'))) {
            return redirect()->to($redirectTo);
        }

        return redirect()->back();
    }
}
