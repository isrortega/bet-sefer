<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Edition;
use App\Support\TreeEditor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class CategoryAdminController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($request->user()->can('taxonomy.manage'), 403);

        return Inertia::render('Staff/Categories', [
            'categories' => Category::query()->orderBy('path')->get()->map(fn (Category $c) => [
                'id' => $c->id,
                'name' => $c->name,
                'slug' => $c->slug,
                'parent_id' => $c->parent_id,
                'depth' => $c->depth,
                'children_count' => $c->children()->count(),
            ])->values(),
            'parents' => Category::query()->orderBy('path')->get(['id', 'name', 'depth']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->can('taxonomy.manage'), 403);

        $data = $this->validateData($request, null);

        TreeEditor::create(new Category, $data);

        return back()->with('message', __('admin.saved'));
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        abort_unless($request->user()->can('taxonomy.manage'), 403);

        $data = $this->validateData($request, $category);

        try {
            TreeEditor::update($category, $data);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return back()->with('message', __('admin.saved'));
    }

    public function destroy(Request $request, Category $category): RedirectResponse
    {
        abort_unless($request->user()->can('taxonomy.manage'), 403);

        try {
            TreeEditor::assertLeaf($category);
        } catch (ValidationException) {
            return back()->with('error', __('admin.children_first'));
        }

        \DB::transaction(function () use ($category) {
            Edition::where('category_id', $category->id)->update(['category_id' => null]);
            $category->delete();
        });

        return back()->with('message', __('admin.deleted'));
    }

    /**
     * @return array<string, mixed>
     */
    private function validateData(Request $request, ?Category $category): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:160'],
            'parent_id' => ['nullable', 'integer', 'exists:categories,id'],
        ];

        if ($category !== null) {
            $rules['slug'] = ['nullable', 'string', 'max:160', Rule::unique('categories', 'slug')->ignore($category->id)];
        } else {
            $rules['slug'] = ['nullable', 'string', 'max:160'];
        }

        return $request->validate($rules);
    }
}
