<?php

namespace App\Http\Controllers\Public;

use App\Enums\CopyStatus;
use App\Http\Controllers\Controller;
use App\Models\Edition;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CatalogController extends Controller
{
    public function index(Request $request): Response
    {
        $q = trim((string) $request->query('q'));

        $query = Edition::query()->withCount(['copies' => fn ($c) => $c->where('status', CopyStatus::Available)]);

        if ($q !== '') {
            if (preg_match('/^\d{9,13}[Xx]?$/', preg_replace('/[^0-9Xx]/', '', $q))) {
                $query->where('isbn_13', preg_replace('/[^0-9Xx]/', '', $q));
            } else {
                $query->where(function ($sub) use ($q) {
                    $sub->whereRaw("search_vector @@ websearch_to_tsquery('simple', ?)", [$q])
                        ->orWhereHas('authors', fn ($a) => $a->where('name', 'ilike', "%{$q}%"))
                        ->orWhereHas('tags', fn ($t) => $t->where('name', 'ilike', "%{$q}%"));
                });
            }
        }

        $editions = $query->orderBy('title')->paginate(24)->withQueryString();

        $items = $editions->through(fn (Edition $edition) => [
            'isbn' => $edition->isbn_13,
            'title' => $edition->title,
            'authors' => $edition->authorNames(),
            'published_year' => $edition->published_year,
            'loan_type' => $edition->loan_type->value,
            'available' => (int) $edition->copies_count,
        ]);

        return Inertia::render('Catalog/Index', [
            'q' => $q,
            'items' => $items,
        ]);
    }
}
