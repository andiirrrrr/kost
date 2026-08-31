<?php

namespace App\Filament\Admin\Pages\Auth;

use App\Models\Setting;
use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Filament\Auth\Pages\Login;
use Filament\Facades\Filament;
use Illuminate\Auth\SessionGuard;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Validation\ValidationException;

class AdminLogin extends Login
{
    protected string $view = 'filament.admin.pages.auth.login';

    public function getLayout(): string
    {
        return 'filament.admin.layouts.auth';
    }

    public function hasLogo(): bool
    {
        return false;
    }

    public function getTitle(): string|Htmlable
    {
        return 'Login Pengelola';
    }

    public function getHeading(): string|Htmlable|null
    {
        return null;
    }

    public function getSubheading(): string|Htmlable|null
    {
        return null;
    }

    public function getBusinessName(): string
    {
        return (string) Setting::get('business_name', 'Kost Bahagia');
    }

    public function authenticate(): ?LoginResponse
    {
        $this->validate([
            'data.email' => ['required', 'string', 'email'],
            'data.password' => ['required', 'string'],
        ]);

        $credentials = [
            'email' => $this->data['email'] ?? '',
            'password' => $this->data['password'] ?? '',
        ];
        $remember = (bool) ($this->data['remember'] ?? false);

        /** @var SessionGuard $authGuard */
        $authGuard = Filament::auth();

        if (! $authGuard->attemptWhen($credentials, fn (Authenticatable $user): bool => $this->isUserAllowedToAccessPanel($user), $remember)) {
            $this->throwFailureValidationException();
        }

        session()->regenerate();

        return app(LoginResponse::class);
    }

    protected function throwFailureValidationException(): never
    {
        throw ValidationException::withMessages([
            'data.email' => 'Email atau password tidak sesuai.',
        ]);
    }
}
