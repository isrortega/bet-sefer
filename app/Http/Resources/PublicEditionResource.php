<?php

namespace App\Http\Resources;

use App\Enums\CopyStatus;
use App\Enums\LoanType;
use App\Models\Copy;
use App\Models\Edition;
use Illuminate\Support\Carbon;

/**
 * Builds public payloads from an explicit allow-list so a new model column can
 * never leak into the anonymous surface.
 */
final class PublicEditionResource
{
    /** @return array<string, mixed> */
    public static function from(Edition $edition): array
    {
        $copies = $edition->copies()->with('location')->get();
        $availableCount = $copies->where('status', CopyStatus::Available)->count();

        return [
            'isbn' => $edition->isbn_13,
            'title' => $edition->title,
            'subtitle' => $edition->subtitle,
            'authors' => $edition->authorNames(),
            'publisher' => $edition->publisher?->name,
            'published_year' => $edition->published_year,
            'edition_statement' => $edition->edition_statement,
            'language' => $edition->language,
            'category' => $edition->category?->humanPath(),
            'tags' => $edition->tags->pluck('name')->all(),
            'format' => $edition->format,
            'pages' => $edition->page_count,
            'summary' => $edition->summary,
            'cover' => $edition->cover_path !== null ? url('storage/'.$edition->cover_path) : null,
            'loan_type' => $edition->loan_type instanceof LoanType ? $edition->loan_type->value : (string) $edition->loan_type,
            'for_loan' => ! $edition->loan_restricted_default && ! $edition->special_material,
            'copies_count' => $copies->count(),
            'available_count' => $availableCount,
            'borrowed_last_year' => self::borrowedLastYear($edition),
            'estimated_available_at' => self::estimatedAvailability($edition),
        ];
    }

    /** Copy payload for the anonymous surface: status + location only. */
    /** @return array<string, mixed> */
    public static function copy(Copy $copy): array
    {
        return [
            'code' => $copy->code,
            'status' => $copy->status instanceof CopyStatus ? $copy->status->value : (string) $copy->status,
            'condition' => $copy->condition,
            'location' => $copy->location?->humanPath(),
        ];
    }

    private static function borrowedLastYear(Edition $edition): int
    {
        return $edition->copies()
            ->withCount(['loans' => fn ($q) => $q->where('checked_out_at', '>=', now()->subYear())])
            ->get()
            ->sum('loans_count');
    }

    /**
     * Aggregate-only estimate: null while anything is available, otherwise the
     * earliest due date among the edition's active loans.
     */
    private static function estimatedAvailability(Edition $edition): ?string
    {
        if ($edition->copies()->where('status', CopyStatus::Available)->exists()) {
            return null;
        }

        $due = $edition->copies()->toBase()
            ->selectRaw('min(loans.due_at) as due')
            ->join('loans', 'loans.copy_id', '=', 'copies.id')
            ->whereNull('loans.returned_at')
            ->value('due');

        return $due !== null ? Carbon::parse($due)->toIso8601String() : null;
    }
}
