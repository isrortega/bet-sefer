<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($request->user()->can('users.manage'), 403);

        $permissions = Permission::query()->orderBy('name')->pluck('name');
        $roles = Role::query()->with('permissions')->orderBy('name')->get()->map(fn (Role $role) => [
            'name' => $role->name,
            'permissions' => $role->permissions->pluck('name')->all(),
        ]);

        return Inertia::render('Staff/Roles', [
            'roles' => $roles->values(),
            'permissions' => $permissions->all(),
        ]);
    }
}
