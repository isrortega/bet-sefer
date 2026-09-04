<?php

namespace App\Http\Controllers\Staff;

use App\Actions\Circulation\CheckoutCopyAction;
use App\Actions\Circulation\RenewLoanAction;
use App\Actions\Circulation\ReturnCopyAction;
use App\Http\Controllers\Controller;
use App\Models\Copy;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class FrontDeskController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($request->user()->can('loans.create'), 403);

        return Inertia::render('Staff/FrontDesk');
    }

    public function checkout(Request $request, CheckoutCopyAction $action): RedirectResponse
    {
        abort_unless($request->user()->can('loans.create'), 403);

        $data = $request->validate([
            'member' => ['required', 'string'],
            'code' => ['required', 'string'],
            'hours' => ['nullable', 'integer', 'min:1', 'max:720'],
        ]);

        $reader = $this->findReader($data['member']);
        $copy = Copy::with('edition')->where('code', strtoupper($data['code']))->first();

        if ($copy === null || $reader === null) {
            return back()->with('error', 'Reader or copy code was not found.');
        }

        try {
            $loan = $action->handle($copy, $reader, $request->user(), isset($data['hours']) ? (int) $data['hours'] : null);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }

        return back()->with('message', "Checked out — due {$loan->due_at->format('M j')}.");
    }

    public function checkin(Request $request, ReturnCopyAction $action): RedirectResponse
    {
        abort_unless($request->user()->can('loans.return'), 403);

        $data = $request->validate(['code' => ['required', 'string']]);
        $copy = Copy::where('code', strtoupper($data['code']))->first();

        if ($copy === null) {
            return back()->with('error', 'Copy code was not found.');
        }

        try {
            $action->handle($copy, $request->user());
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return back()->with('message', 'Checked in — the copy will go back to the shelf.');
    }

    public function renew(Request $request, RenewLoanAction $action): RedirectResponse
    {
        abort_unless($request->user()->can('loans.renew'), 403);

        $data = $request->validate(['code' => ['required', 'string']]);
        $loan = Loan::where('code', strtoupper($data['code']))->first();

        if ($loan === null) {
            return back()->with('error', 'Loan receipt was not found.');
        }

        try {
            $action->handle($loan, $request->user());
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return back()->with('message', 'Loan renewed.');
    }

    private function findReader(string $member): ?User
    {
        return User::query()
            ->where('member_code', strtoupper($member))
            ->orWhere('email', strtolower($member))
            ->first();
    }
}
