<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

final class AuthController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('auth/terminal-access');
    }

    public function register(): Response
    {
        return Inertia::render('auth/register');
    }

    public function storeRegistration(RegisterRequest $request): RedirectResponse
    {
        $user = User::create(
            $request->safe()->only(['name', 'email', 'password']),
        );

        Auth::guard('web')->login($user);

        $request->session()->regenerate();

        return redirect()->route('ciphers.catalog');
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('terminal-access');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $credentials = $request->validated();

        if (! Auth::guard('web')->attempt([
            'email' => $credentials['email'],
            'password' => $credentials['password'],
        ], (bool) ($credentials['remember'] ?? false))) {
            return back()
                ->withErrors(['email' => 'Las credenciales no coinciden con un operador activo.'])
                ->onlyInput('email');
        }

        $request->session()->regenerate();

        return redirect()->intended(route('ciphers.catalog'));
    }
}
