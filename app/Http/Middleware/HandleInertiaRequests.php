<?php

namespace App\Http\Middleware;

use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user !== null ? $this->userPayload($user) : null,
            ],
            'flash' => [
                'message' => $request->session()->get('message'),
                'error' => $request->session()->get('error'),
            ],
            'locale' => app()->getLocale(),
            'csrf_token' => csrf_token(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function userPayload(User $user): array
    {
        $roles = $user->roles->pluck('name')->all();

        return [
            'ulid' => $user->ulid,
            'name' => $user->name,
            'email' => $user->email,
            'member_code' => $user->member_code,
            'status' => $user->status->value,
            'roles' => $roles,
            'capabilities' => [
                'front_desk' => $user->can('loans.create'),
                'shelving' => $user->can('copies.transition'),
                'readers' => $user->can('users.manage') || $user->can('users.verify_identity'),
                'demand' => $user->can('demand.manage'),
            ],
        ];
    }
}
