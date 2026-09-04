<?php

namespace App\Http\Controllers\Staff;

use App\Enums\CopyStatus;
use App\Exceptions\InvalidCopyTransition;
use App\Http\Controllers\Controller;
use App\Models\Copy;
use App\Services\Circulation\CopyStateMachine;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ShelvingController extends Controller
{
    public function index(): Response
    {
        abort_unless(request()->user()->can('copies.transition'), 403);

        $queue = Copy::query()
            ->with(['edition', 'location'])
            ->whereIn('status', [CopyStatus::AtReception, CopyStatus::InTransit])
            ->orderBy('status_changed_at')
            ->get()
            ->map(fn ($copy) => [
                'code' => $copy->code,
                'title' => $copy->edition->title,
                'status' => $copy->status->value,
                'destination' => $copy->location?->humanPath(),
            ]);

        return Inertia::render('Staff/Shelving', ['queue' => $queue]);
    }

    public function advance(Request $request, CopyStateMachine $machine): RedirectResponse
    {
        abort_unless($request->user()->can('copies.transition'), 403);

        $data = $request->validate(['code' => ['required', 'string']]);
        $copy = Copy::where('code', strtoupper($data['code']))->first();

        if ($copy === null) {
            return back()->with('error', 'Copy code was not found.');
        }

        $target = $copy->status === CopyStatus::AtReception ? CopyStatus::InTransit : CopyStatus::Available;

        try {
            $machine->transition($copy, $target, $request->user());
        } catch (InvalidCopyTransition $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('message', $target === CopyStatus::Available ? 'Shelved.' : 'Picked up — heading to the shelf.');
    }
}
