<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\LoanPolicy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PolicyAdminController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($request->user()->can('policies.manage'), 403);

        return Inertia::render('Staff/Policies', [
            'policies' => LoanPolicy::query()->orderBy('id')->get(),
        ]);
    }

    public function update(Request $request, LoanPolicy $policy): RedirectResponse
    {
        abort_unless($request->user()->can('policies.manage'), 403);

        $data = $request->validate([
            'default_hours' => ['required', 'integer', 'min:1', 'max:8760'],
            'min_hours' => ['required', 'integer', 'min:1', 'max:8760'],
            'max_hours' => ['required', 'integer', 'min:1', 'max:8760'],
            'renewals_allowed' => ['required', 'integer', 'min:0', 'max:20'],
            'special_material_factor' => ['required', 'numeric', 'between:0.05,1'],
            'grace_hours' => ['required', 'integer', 'min:0', 'max:8760'],
            'max_active_loans_per_user' => ['required', 'integer', 'min:1', 'max:50'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        if ($data['min_hours'] > $data['default_hours'] || $data['default_hours'] > $data['max_hours']) {
            return back()->with('error', __('policies.range_error'));
        }

        $policy->fill([
            'default_hours' => $data['default_hours'],
            'min_hours' => $data['min_hours'],
            'max_hours' => $data['max_hours'],
            'renewals_allowed' => $data['renewals_allowed'],
            'special_material_factor' => $data['special_material_factor'],
            'grace_hours' => $data['grace_hours'],
            'max_active_loans_per_user' => $data['max_active_loans_per_user'],
            'is_active' => $request->boolean('is_active'),
        ])->save();

        return back()->with('message', __('admin.saved'));
    }
}
