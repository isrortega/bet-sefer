<?php

namespace App\Http\Controllers\Staff;

use App\Actions\Catalog\CopyActions;
use App\Actions\Catalog\DeleteEditionAction;
use App\Actions\Catalog\StoreEditionAction;
use App\Actions\Catalog\UpdateEditionAction;
use App\Enums\CopyStatus;
use App\Enums\LoanType;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Copy;
use App\Models\Edition;
use App\Models\Location;
use App\Services\Metadata\BookMetadata;
use App\Services\Metadata\PublicIsbnLookupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class CatalogueController extends Controller
{
    public const FORMATS = ['hardcover', 'paperback', 'spiral', 'magazine', 'other'];

    public const LANGUAGES = ['en', 'es', 'de', 'fr', 'it', 'pt', 'ja', 'ru'];

    public const CONDITIONS = ['new', 'good', 'fair', 'poor'];

    public function index(Request $request): Response
    {
        abort_unless($request->user()->can('catalog.view'), 403);

        $query = Edition::query()->withCount(['copies', 'copies as available_count' => fn ($c) => $c->where('status', CopyStatus::Available)]);

        if ($q = trim((string) $request->query('q'))) {
            $isbn = preg_replace('/[^0-9Xx]/', '', $q);
            $query->where(function ($sub) use ($q, $isbn) {
                $sub->where('title', 'ilike', "%{$q}%")
                    ->orWhereHas('authors', fn ($a) => $a->where('name', 'ilike', "%{$q}%"))
                    ->orWhereHas('tags', fn ($t) => $t->where('name', 'ilike', "%{$q}%"));
                if (strlen($isbn) >= 10) {
                    $sub->orWhere('isbn_13', $isbn)->orWhere('isbn_10', $isbn);
                }
            });
        }

        if ($category = (int) $request->query('category')) {
            $query->where('category_id', $category);
        }

        $editions = $query->orderBy('title')->paginate(25)->withQueryString();

        return Inertia::render('Staff/Catalog', [
            'editions' => $editions->through(fn (Edition $e) => [
                'ulid' => $e->ulid,
                'title' => $e->title,
                'authors' => $e->authorNames(),
                'published_year' => $e->published_year,
                'isbn' => $e->isbn_13,
                'copies_count' => (int) $e->copies_count,
                'available_count' => (int) ($e->available_count ?? 0),
            ]),
            'categories' => Category::query()->orderBy('path')->get(['id', 'name', 'depth']),
        ]);
    }

    public function create(Request $request, PublicIsbnLookupService $lookup): Response
    {
        abort_unless($request->user()->can('editions.create'), 403);

        $prefill = [];
        $existing = null;

        if ($isbn = preg_replace('/[^0-9Xx]/', '', (string) $request->query('isbn', ''))) {
            $existing = Edition::where('isbn_13', $isbn)->first();
            if ($existing === null) {
                $prefill = $this->prefillFrom($lookup->lookup($isbn));
                $prefill['isbn_13'] = $isbn;
            }
        }

        return Inertia::render('Staff/EditionForm', [
            'mode' => 'create',
            'prefill' => $prefill,
            'existing_edition' => $existing?->ulid,
            'categories' => Category::query()->orderBy('path')->get(['id', 'name', 'depth']),
            'locations' => Location::query()->orderBy('path')->get(['id', 'name', 'depth']),
            'formats' => self::FORMATS,
            'languages' => self::LANGUAGES,
            'loan_types' => array_column(LoanType::cases(), 'value'),
            'conditions' => self::CONDITIONS,
        ]);
    }

    public function store(Request $request, StoreEditionAction $action): RedirectResponse
    {
        abort_unless($request->user()->can('editions.create'), 403);

        $input = $this->validateEdition($request, null);
        $input['actor'] = $request->user()->id;

        $edition = $action->handle($input);

        return redirect()->route('staff.catalog.edit', $edition->ulid)
            ->with('message', __('catalog.edition_created'));
    }

    public function edit(Request $request, Edition $edition): Response
    {
        abort_unless($request->user()->can('editions.update'), 403);

        $copies = $edition->copies()->with('location')->orderBy('code')->get();

        return Inertia::render('Staff/EditionForm', [
            'mode' => 'edit',
            'edition' => [
                'ulid' => $edition->ulid,
                'title' => $edition->title,
                'subtitle' => $edition->subtitle,
                'edition_statement' => $edition->edition_statement,
                'isbn_13' => $edition->isbn_13,
                'publisher' => $edition->publisher ? $edition->publisher->name : '',
                'authors' => $edition->authors->pluck('name')->implode(', '),
                'tags' => $edition->tags->pluck('name')->implode(', '),
                'category_id' => $edition->category_id,
                'language' => $edition->language,
                'format' => $edition->format,
                'published_year' => $edition->published_year,
                'page_count' => $edition->page_count,
                'loan_type' => $edition->loan_type->value,
                'special_material' => (bool) $edition->special_material,
                'loan_restricted_default' => (bool) $edition->loan_restricted_default,
                'summary' => $edition->summary,
                'internal_notes' => $edition->internal_notes,
                'copies' => $copies->map(fn (Copy $copy) => [
                    'id' => $copy->id,
                    'code' => $copy->code,
                    'status' => $copy->status->value,
                    'location_id' => $copy->location_id,
                    'location_label' => $copy->location?->humanPath(),
                    'condition' => $copy->condition,
                    'loan_restricted' => $copy->loan_restricted,
                ])->values(),
            ],
            'categories' => Category::query()->orderBy('path')->get(['id', 'name', 'depth']),
            'locations' => Location::query()->orderBy('path')->get(['id', 'name', 'depth']),
            'formats' => self::FORMATS,
            'languages' => self::LANGUAGES,
            'loan_types' => array_column(LoanType::cases(), 'value'),
            'conditions' => self::CONDITIONS,
            'can_delete_edition' => $request->user()->can('editions.delete'),
        ]);
    }

    public function update(Request $request, Edition $edition, UpdateEditionAction $action): RedirectResponse
    {
        abort_unless($request->user()->can('editions.update'), 403);

        $input = $this->validateEdition($request, $edition);

        try {
            $action->handle($edition, $input);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return back()->with('message', __('catalog.edition_updated'));
    }

    public function destroy(Request $request, Edition $edition, DeleteEditionAction $action): RedirectResponse
    {
        abort_unless($request->user()->can('editions.delete'), 403);

        try {
            $action->handle($edition);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return redirect()->route('staff.catalog.index')->with('message', __('catalog.edition_deleted'));
    }

    public function storeCopy(Request $request, Edition $edition): RedirectResponse
    {
        abort_unless($request->user()->can('copies.create'), 403);

        $data = $request->validate([
            'location_id' => ['nullable', 'integer', 'exists:locations,id'],
            'condition' => ['nullable', Rule::in(self::CONDITIONS)],
        ]);

        CopyActions::store($edition, $data);

        return back()->with('message', __('catalog.copy_added'));
    }

    public function updateCopy(Request $request, Copy $copy): RedirectResponse
    {
        abort_unless($request->user()->can('copies.update'), 403);

        $data = $request->validate([
            'location_id' => ['nullable', 'integer', 'exists:locations,id'],
            'condition' => ['nullable', Rule::in(self::CONDITIONS)],
            'loan_restricted' => ['nullable', Rule::in(['1', '0', 'inherit'])],
        ]);

        CopyActions::update($copy, $data);

        return back()->with('message', __('catalog.copy_updated'));
    }

    public function destroyCopy(Request $request, Copy $copy): RedirectResponse
    {
        abort_unless($request->user()->can('copies.delete'), 403);

        try {
            CopyActions::destroy($copy);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return back()->with('message', __('catalog.copy_deleted'));
    }

    /**
     * @return array<string, mixed>
     */
    private function validateEdition(Request $request, ?Edition $edition): array
    {
        $isbn = preg_replace('/[^0-9Xx]/', '', (string) $request->input('isbn_13', ''));

        if ($isbn !== '') {
            $duplicate = Edition::where('isbn_13', $isbn)->whereKeyNot($edition?->id)->exists();
            if ($duplicate) {
                throw ValidationException::withMessages(['isbn_13' => __('catalog.isbn_exists')]);
            }
        }

        $rules = [
            'title' => ['required', 'string', 'max:500'],
            'isbn_13' => ['nullable', 'string', 'max:17'],
            'subtitle' => ['nullable', 'string', 'max:500'],
            'edition_statement' => ['nullable', 'string', 'max:120'],
            'authors' => ['nullable', 'string', 'max:500'],
            'publisher' => ['nullable', 'string', 'max:160'],
            'tags' => ['nullable', 'string', 'max:500'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'language' => ['required', Rule::in(self::LANGUAGES)],
            'format' => ['required', Rule::in(self::FORMATS)],
            'loan_type' => ['required', Rule::in(array_column(LoanType::cases(), 'value'))],
            'published_year' => ['nullable', 'integer', 'between:1000,2100'],
            'page_count' => ['nullable', 'integer', 'min:1'],
            'summary' => ['nullable', 'string'],
            'internal_notes' => ['nullable', 'string'],
            'special_material' => ['sometimes', 'boolean'],
            'loan_restricted_default' => ['sometimes', 'boolean'],
        ];

        $data = $request->validate($rules);
        $data['isbn_13'] = $isbn !== '' ? $isbn : null;
        $data['special_material'] = $request->boolean('special_material');
        $data['loan_restricted_default'] = $request->boolean('loan_restricted_default');

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    private function prefillFrom(?BookMetadata $meta): array
    {
        if ($meta === null) {
            return [];
        }

        return [
            'title' => $meta->title,
            'authors' => implode(', ', $meta->authors),
            'publisher' => $meta->publisher ?? '',
            'published_year' => $meta->publishedYear,
            'language' => $meta->language ?? 'en',
            'page_count' => $meta->pageCount,
            'summary' => $meta->summary,
        ];
    }
}
