<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    public function create(): Response
    {
        if (Auth::check()) {
            return Inertia::render('Home', ['name' => 'Bet-Sefer']);
        }

        return Inertia::render('Auth/Login', [
            'ssoEnabled' => config('services.google.client_id') !== null && config('services.google.client_id') !== '',
            'googleUrl' => route('auth.google'),
        ]);
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $user = $request->user();
        if ($user instanceof User) {
            if ($user->isActive() === false && ($user->status->value === 'suspended' || $user->blocked_until?->isFuture())) {
                Auth::logout();
                throw ValidationException::withMessages([
                    'email' => 'This account is suspended. Contact the library administrator.',
                ]);
            }
        }

        $request->session()->regenerate();

        return redirect()->intended(route('home'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
