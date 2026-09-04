<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\CrockfordCode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Spatie\Permission\Models\Role;
use Throwable;

class GoogleController extends Controller
{
    public function redirect(): \Symfony\Component\HttpFoundation\RedirectResponse
    {
        if (! $this->enabled()) {
            return redirect()->route('login')->with('error', 'Google sign-in is not enabled.');
        }

        return Socialite::driver('google')->redirect();
    }

    public function callback(): RedirectResponse
    {
        if (! $this->enabled()) {
            return redirect()->route('login')->with('error', 'Google sign-in is not enabled.');
        }

        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (Throwable) {
            return redirect()->route('login')->with('error', 'Google sign-in did not complete. Try again.');
        }

        $email = strtolower((string) $googleUser->getEmail());
        if ($email === '') {
            return redirect()->route('login')->with('error', 'Google did not return an email for this account.');
        }

        $user = User::where('email', $email)->first();

        if ($user === null) {
            $closed = User::onlyTrashed()->where('email', $email)->first();
            if ($closed !== null) {
                return redirect()->route('login')->with('error', 'This account was closed. Contact the library administrator.');
            }

            $user = User::create([
                'ulid' => (string) Str::ulid(),
                'name' => (string) ($googleUser->getName() ?? $googleUser->getNickname() ?? 'Reader'),
                'email' => $email,
                'email_verified_at' => now(),
                'google_id' => (string) $googleUser->getId(),
                'avatar_url' => $googleUser->getAvatar(),
                'status' => UserStatus::PendingIdentity,
                'member_code' => CrockfordCode::generate(),
                'locale' => 'en',
            ]);
            Role::findOrCreate('reader', 'web');
            $user->syncRoles(['reader']);
        } elseif ($user->google_id === null) {
            // The email is proven by Google; link the account, roles are preserved.
            $user->forceFill(['google_id' => (string) $googleUser->getId(), 'email_verified_at' => $user->email_verified_at ?? now()])->save();
        }

        if ($user->status === UserStatus::Suspended || $user->blocked_until?->isFuture()) {
            return redirect()->route('login')->with('error', 'This account is suspended. Contact the library administrator.');
        }

        Auth::login($user, true);
        session()->regenerate();

        return redirect()->intended(route('home'));
    }

    private function enabled(): bool
    {
        $id = config('services.google.client_id');

        return $id !== null && $id !== '';
    }
}
