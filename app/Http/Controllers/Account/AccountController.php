<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class AccountController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $loans = $user->loans()
            ->with(['copy.edition', 'copy.location'])
            ->whereNull('returned_at')
            ->orderBy('due_at')
            ->get();

        return Inertia::render('Account/Dashboard', [
            'member' => [
                'code' => $user->member_code,
                'status' => $user->status->value,
                'name' => $user->name,
            ],
            'loans' => $loans->map(fn ($loan) => [
                'code' => $loan->code,
                'title' => $loan->copy->edition->title,
                'due_at' => $loan->due_at->toIso8601String(),
                'overdue' => $loan->isOverdue(),
                'copy_code' => $loan->copy->code,
            ])->values(),
        ]);
    }

    public function history(Request $request): Response
    {
        $loans = $request->user()->loans()
            ->with(['copy.edition'])
            ->whereNotNull('returned_at')
            ->latest('returned_at')
            ->paginate(20);

        return Inertia::render('Account/History', [
            'loans' => $loans->through(fn ($loan) => [
                'code' => $loan->code,
                'title' => $loan->copy->edition->title,
                'checked_out_at' => $loan->checked_out_at->toIso8601String(),
                'returned_at' => $loan->returned_at?->toIso8601String(),
            ]),
        ]);
    }

    public function card(Request $request): Response
    {
        $user = $request->user();
        $qr = (string) QrCode::size(220)->generate($user->member_code);

        return Inertia::render('Account/Card', [
            'member' => [
                'code' => $user->member_code,
                'name' => $user->name,
                'qr' => $qr,
            ],
        ]);
    }
}
