<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\DemandEvent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DemandController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($request->user()->can('demand.manage'), 403);

        $events = DemandEvent::query()
            ->where('type', 'acquisition_suggestion')
            ->latest('created_at')
            ->paginate(50)
            ->withQueryString();

        $totals = [
            'total' => DemandEvent::where('type', 'acquisition_suggestion')->count(),
            'pending' => DemandEvent::where('type', 'acquisition_suggestion')->whereNull('resolved_at')->count(),
        ];

        return Inertia::render('Staff/Demand', [
            'suggestions' => $events->through(fn (DemandEvent $event) => [
                'id' => $event->id,
                'isbn' => $event->isbn,
                'created_at' => $event->created_at?->toIso8601String(),
                'resolved_at' => $event->resolved_at?->toIso8601String(),
                'meta' => $event->metadata,
            ]),
            'totals' => $totals,
        ]);
    }

    public function resolve(Request $request, DemandEvent $demandEvent): RedirectResponse
    {
        abort_unless($request->user()->can('demand.manage'), 403);

        $demandEvent->forceFill(['resolved_at' => $demandEvent->resolved_at ?? now()])->save();

        activity()
            ->performedOn($demandEvent)
            ->causedBy($request->user())
            ->log('acquisition_suggestion_resolved');

        return back()->with('message', __('demand.handled_flash'));
    }
}
