<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LocaleController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $locale = $request->input('locale', 'en');
        abort_unless(in_array($locale, ['en', 'es'], true), 422);

        session(['locale' => $locale]);
        app()->setLocale($locale);

        if ($user = $request->user()) {
            $user->forceFill(['locale' => $locale])->save();
        }

        return redirect()->back();
    }
}
