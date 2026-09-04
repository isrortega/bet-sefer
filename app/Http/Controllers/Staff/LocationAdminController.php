<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Copy;
use App\Models\Location;
use App\Support\TreeEditor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class LocationAdminController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($request->user()->can('taxonomy.manage'), 403);

        return Inertia::render('Staff/Locations', [
            'locations' => Location::query()->orderBy('path')->get()->map(fn (Location $l) => [
                'id' => $l->id,
                'name' => $l->name,
                'code' => $l->code,
                'type' => $l->type,
                'parent_id' => $l->parent_id,
                'depth' => $l->depth,
                'children_count' => $l->children()->count(),
            ])->values(),
            'parents' => Location::query()->orderBy('path')->get(['id', 'name', 'depth']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->can('taxonomy.manage'), 403);

        $data = $this->validateData($request, null);
        $data['type'] = (string) $request->input('type', 'shelf');

        TreeEditor::create(new Location, $data);

        return back()->with('message', __('admin.saved'));
    }

    public function update(Request $request, Location $location): RedirectResponse
    {
        abort_unless($request->user()->can('taxonomy.manage'), 403);

        $data = $this->validateData($request, $location);

        try {
            TreeEditor::update($location, $data);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return back()->with('message', __('admin.saved'));
    }

    public function destroy(Request $request, Location $location): RedirectResponse
    {
        abort_unless($request->user()->can('taxonomy.manage'), 403);

        try {
            TreeEditor::assertLeaf($location);
        } catch (ValidationException) {
            return back()->with('error', __('admin.children_first'));
        }

        \DB::transaction(function () use ($location) {
            Copy::where('location_id', $location->id)->update(['location_id' => null]);
            $location->delete();
        });

        return back()->with('message', __('admin.deleted'));
    }

    /**
     * @return array<string, mixed>
     */
    private function validateData(Request $request, ?Location $location): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:160'],
            'code' => ['required', 'string', 'max:24', Rule::unique('locations', 'code')->ignore($location?->id)],
            'parent_id' => ['nullable', 'integer', 'exists:locations,id'],
        ];

        return $request->validate($rules);
    }
}
