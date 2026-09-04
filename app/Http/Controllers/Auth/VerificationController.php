<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class VerificationController extends Controller
{
    public function verify(Request $request): RedirectResponse
    {
        $user = User::where('ulid', $request->route('user'))->firstOrFail();

        abort_unless(hash_equals(sha1($user->email), (string) $request->route('hash')), 403);

        if ($user->email_verified_at === null) {
            $user->forceFill([
                'email_verified_at' => now(),
                'status' => $user->status === UserStatus::PendingEmail ? UserStatus::PendingIdentity : $user->status,
            ])->save();
        }

        return redirect()->route('login')->with(
            'message',
            'Email verified. Bring your ID to the front desk to activate borrowing.'
        );
    }
}
