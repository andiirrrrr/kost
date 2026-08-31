<?php

namespace App\Http\Controllers\Auth;

use App\Enums\TenantStatus;
use App\Http\Controllers\Controller;
use App\Services\LandingPageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class TenantLoginController extends Controller
{
    public function create(Request $request, LandingPageService $landingPage): View|RedirectResponse
    {
        if ($request->user()?->hasRole('tenant')) {
            return redirect()->route('tenant.dashboard');
        }

        if ($request->user()?->hasRole('owner')) {
            return redirect('/admin');
        }

        return view('auth.login', ['settings' => $landingPage->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'remember' => ['sometimes', 'boolean'],
        ]);

        if (! Auth::attempt([
            'email' => $credentials['email'],
            'password' => $credentials['password'],
        ], $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'Email atau password tidak sesuai.'])->onlyInput('email');
        }

        $request->session()->regenerate();
        $user = $request->user();

        if ($user?->hasRole('owner')) {
            return redirect()->intended('/admin');
        }

        if (! $user?->hasRole('tenant') || $user->tenant?->status !== TenantStatus::ACTIVE) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()->withErrors(['email' => 'Akun ini tidak memiliki akses portal penghuni.']);
        }

        return redirect()->intended(route('tenant.dashboard'));
    }
}
