<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Users\RegisterReaderAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterReaderRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    public function create(): Response
    {
        if (Auth::check()) {
            return Inertia::render('Home', ['name' => 'Bet-Sefer']);
        }

        return Inertia::render('Auth/Register');
    }

    public function store(RegisterReaderRequest $request, RegisterReaderAction $action): RedirectResponse
    {
        $action->handle($request->validated());

        return redirect()->route('login')->with('message', 'Account created. Check your inbox to verify your email.');
    }
}
