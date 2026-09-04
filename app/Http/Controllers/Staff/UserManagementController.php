<?php

namespace App\Http\Controllers\Staff;

use App\Actions\Users\CreateUserAction;
use App\Actions\Users\UpdateUserAction;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class UserManagementController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($request->user()->can('users.manage'), 403);

        $query = User::query()->with('roles');

        if ($q = trim((string) $request->query('q'))) {
            $query->where(fn ($sub) => $sub
                ->where('name', 'ilike', "%{$q}%")
                ->orWhere('email', 'ilike', "%{$q}%")
                ->orWhere('member_code', 'ilike', "%{$q}%"));
        }

        $users = $query->orderBy('name')->paginate(25)->withQueryString();

        return Inertia::render('Staff/Users', [
            'users' => $users->through(fn (User $user) => $this->row($user)),
            'roles' => CreateUserAction::ROLES,
        ]);
    }

    public function store(Request $request, CreateUserAction $action): RedirectResponse
    {
        abort_unless($request->user()->can('users.manage'), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'email' => ['required', 'email', 'max:190', Rule::unique('users', 'email')],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', Rule::in(CreateUserAction::ROLES)],
        ]);

        $user = $action->handle($data, $request->user());

        return back()->with('message', __('admin.user_created', ['name' => $user->name]));
    }

    public function update(Request $request, User $user, UpdateUserAction $action): RedirectResponse
    {
        abort_unless($request->user()->can('users.manage'), 403);

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:160'],
            'email' => ['sometimes', 'email', 'max:190', Rule::unique('users', 'email')->ignore($user->id)],
            'role' => ['sometimes', Rule::in(CreateUserAction::ROLES)],
        ]);

        try {
            $action->handle($user, $request->user(), $data);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return back()->with('message', __('admin.user_updated_flash'));
    }

    public function activate(Request $request, User $user, UpdateUserAction $action): RedirectResponse
    {
        abort_unless($request->user()->can('users.manage'), 403);

        try {
            $action->activate($user, $request->user());
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return back()->with('message', __('admin.user_activated'));
    }

    public function suspend(Request $request, User $user, UpdateUserAction $action): RedirectResponse
    {
        abort_unless($request->user()->can('users.manage'), 403);

        $reason = (string) $request->input('reason', '');

        try {
            $action->suspend($user, $request->user(), $reason !== '' ? $reason : null);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return back()->with('message', __('admin.user_suspended'));
    }

    public function close(Request $request, User $user, UpdateUserAction $action): RedirectResponse
    {
        abort_unless($request->user()->can('users.manage'), 403);

        try {
            $action->close($user, $request->user());
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return back()->with('message', __('admin.user_closed'));
    }

    public function restore(Request $request, string $ulid): RedirectResponse
    {
        abort_unless($request->user()->can('users.manage'), 403);

        $user = User::onlyTrashed()->where('ulid', $ulid)->firstOrFail();
        $user->restore();
        $user->forceFill(['status' => UserStatus::PendingIdentity, 'identity_verified_at' => null])->save();

        return back()->with('message', __('readers.reopened'));
    }

    /**
     * @return array<string, mixed>
     */
    private function row(User $user): array
    {
        return [
            'ulid' => $user->ulid,
            'name' => $user->name,
            'email' => $user->email,
            'member_code' => $user->member_code,
            'status' => $user->status instanceof UserStatus ? $user->status->value : (string) $user->status,
            'roles' => $user->roles->pluck('name'),
            'deleted' => $user->deleted_at !== null,
        ];
    }
}
