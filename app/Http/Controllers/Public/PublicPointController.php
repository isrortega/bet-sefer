<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Resources\PublicEditionResource;
use App\Models\Copy;
use App\Models\DemandEvent;
use App\Models\Edition;
use App\Services\Metadata\BookMetadata;
use App\Services\Metadata\PublicIsbnLookupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PublicPointController extends Controller
{
    public function copy(Request $request, string $code): Response
    {
        $copy = Copy::with(['edition.authors', 'edition.tags', 'edition.category', 'edition.publisher'])
            ->where('code', strtoupper($code))
            ->firstOrFail();

        $payload = PublicEditionResource::from($copy->edition);
        $payload['copy'] = PublicEditionResource::copy($copy);
        $payload['for_loan'] = ! $copy->isLoanRestricted();

        return $this->render('Public/Copy', $payload, $request);
    }

    public function lookup(Request $request): Response
    {
        $isbn = preg_replace('/[^0-9Xx]/', '', (string) $request->query('isbn', ''));

        return $this->lookupByIsbn($request, $isbn);
    }

    public function isbn(Request $request, string $isbn): Response
    {
        return $this->lookupByIsbn($request, preg_replace('/[^0-9Xx]/', '', $isbn));
    }

    private function lookupByIsbn(Request $request, string $isbn): Response
    {
        if ($isbn === '') {
            return Inertia::render('Public/Lookup', ['lookedUp' => null, 'isbn' => null, 'found' => null, 'suggested' => false, 'external' => null]);
        }

        $edition = Edition::where('isbn_13', $isbn)->orWhere('isbn_10', $isbn)->first();

        if ($edition === null) {
            $this->record($request, 'public_lookup_unavailable', null, $isbn);

            return Inertia::render('Public/Lookup', [
                'lookedUp' => true,
                'isbn' => $isbn,
                'found' => null,
                'suggested' => false,
                'external' => $this->externalPayload(app(PublicIsbnLookupService::class)->lookup($isbn)),
            ]);
        }

        $payload = PublicEditionResource::from($edition->load(['authors', 'tags', 'category', 'publisher']));

        return $this->render('Public/Edition', $payload, $request, $edition);
    }

    public function suggest(Request $request, PublicIsbnLookupService $lookupService): RedirectResponse
    {
        $isbn = preg_replace('/[^0-9Xx]/', '', (string) $request->input('isbn', ''));

        if ($isbn === '' || strlen($isbn) < 10) {
            return back()->with('error', __('public.suggestion_bad_isbn'));
        }

        $metadata = $this->externalPayload($lookupService->lookup($isbn));
        $this->record($request, 'acquisition_suggestion', null, $isbn, $metadata);

        return back()->with('message', __('public.suggestion_thanks'));
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function render(string $component, array $payload, Request $request, ?Edition $edition = null): Response
    {
        $user = $request->user();

        if ($user !== null) {
            $payload['viewer'] = [
                'role' => $user->roles->pluck('name')->first(),
                'can_view_internal_notes' => $user->can('catalog.view_internal_notes'),
            ];

            if ($edition !== null && $user->can('catalog.view_internal_notes')) {
                $payload['internal_notes'] = $edition->internal_notes;
            }
        }

        return Inertia::render($component, $payload);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function externalPayload(?BookMetadata $meta): ?array
    {
        if ($meta === null) {
            return null;
        }

        return [
            'title' => $meta->title,
            'authors' => $meta->authors,
            'subtitle' => $meta->subtitle,
            'publisher' => $meta->publisher,
            'published_year' => $meta->publishedYear,
            'summary' => $meta->summary,
            'cover' => $meta->coverUrl,
        ];
    }

    /**
     * @param  array<string, mixed>|null  $metadata
     */
    private function record(Request $request, string $type, ?int $editionId, ?string $isbn, ?array $metadata = null): void
    {
        DemandEvent::create([
            'type' => $type,
            'edition_id' => $editionId,
            'isbn' => $isbn,
            'query_text' => $isbn,
            'user_id' => $request->user()?->id,
            'ip_hash' => $request->ip() !== null ? hash('sha256', $request->ip().config('app.key')) : null,
            'metadata' => $metadata,
            'created_at' => now(),
        ]);
    }
}
