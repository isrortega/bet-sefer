<?php

namespace App\Http\Controllers\Staff;

use App\Actions\Users\VerifyIdentityAction;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ReaderManagementController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless(
            $request->user()->can('users.manage') || $request->user()->can('users.verify_identity'),
            403
        );

        $query = User::query()->with('roles');

        if ($q = trim((string) $request->query('q'))) {
            $query->where(function ($sub) use ($q) {
                $sub->where('name', 'ilike', "%{$q}%")
                    ->orWhere('email', 'ilike', "%{$q}%")
                    ->orWhere('member_code', 'ilike', "%{$q}%");
            });
        }

        $readers = $query->orderBy('name')->paginate(25)->withQueryString();

        return Inertia::render('Staff/Readers', [
            'readers' => $readers->through(fn (User $user) => [
                'ulid' => $user->ulid,
                'name' => $user->name,
                'email' => $user->email,
                'member_code' => $user->member_code,
                'status' => $user->status instanceof UserStatus ? $user->status->value : (string) $user->status,
                'deleted' => $user->deleted_at !== null,
                'roles' => $user->roles->pluck('name'),
                'verified_at' => $user->identity_verified_at?->toIso8601String(),
            ]),
            'canVerify' => $request->user()->can('users.verify_identity'),
        ]);
    }

    public function verify(Request $request, User $user, VerifyIdentityAction $action): RedirectResponse
    {
        abort_unless($request->user()->can('users.verify_identity'), 403);

        $data = $request->validate([
            'document_type' => ['required', Rule::in(['CC', 'CE', 'passport'])],
            'document_number' => ['required', 'string', 'max:64'],
        ]);

        try {
            $action->handle($user, $request->user(), $data);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }

        return back()->with('message', __('readers.verified', ['name' => $user->name]));
    }

    public function restore(Request $request, string $ulid): RedirectResponse
    {
        abort_unless($request->user()->can('users.manage'), 403);

        $user = User::onlyTrashed()->where('ulid', $ulid)->firstOrFail();
        $user->restore();
        $user->forceFill(['status' => UserStatus::PendingIdentity, 'identity_verified_at' => null])->save();

        return back()->with('message', 'Account reopened. It needs identity verification again before borrowing.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        abort_unless($request->user()->can('users.manage'), 403);

        if ($user->loans()->whereNull('returned_at')->exists()) {
            return back()->with('error', 'This user still has books on loan.');
        }

        $user->delete();

        return back()->with('message', 'Account closed.');
    }
}
