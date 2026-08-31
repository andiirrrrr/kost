<?php

namespace App\Livewire\Tenant;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Profile extends Component
{
    public string $phone = '';

    public string $email = '';

    public string $currentPassword = '';

    public string $password = '';

    public string $passwordConfirmation = '';

    public function mount(): void
    {
        $this->phone = auth()->user()->tenant->phone;
        $this->email = auth()->user()->email;
    }

    public function updateContact(): void
    {
        $user = auth()->user();
        $tenant = $user->tenant;
        $validated = $this->validate([
            'phone' => ['required', 'string', 'max:20', Rule::unique('tenants', 'phone')->ignore($tenant->id), Rule::unique('users', 'phone')->ignore($user->id)],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
        ]);

        DB::transaction(function () use ($user, $tenant, $validated): void {
            $tenant->update(['phone' => $validated['phone'], 'email' => $validated['email']]);
            $user->update(['phone' => $tenant->fresh()->phone, 'email' => $validated['email']]);
        });

        session()->flash('success', 'Kontak berhasil diperbarui.');
    }

    public function updatePassword(): void
    {
        $validated = $this->validate([
            'currentPassword' => ['required', 'current_password:web'],
            'password' => ['required', 'string', 'min:12', 'same:passwordConfirmation'],
            'passwordConfirmation' => ['required'],
        ], ['currentPassword.current_password' => 'Password saat ini tidak sesuai.']);

        auth()->user()->update(['password' => Hash::make($validated['password'])]);
        $this->reset('currentPassword', 'password', 'passwordConfirmation');
        session()->flash('success', 'Password berhasil diubah.');
    }

    public function render()
    {
        return view('livewire.tenant.profile', ['tenant' => auth()->user()->tenant->load('room')]);
    }
}
