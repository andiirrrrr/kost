<?php

namespace App\Livewire\Tenant;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
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
        try {
            $validated = $this->validate([
                'phone' => ['required', 'string', 'regex:/^(?:0|62)[0-9]{8,13}$/', Rule::unique('tenants', 'phone')->ignore($tenant->id), Rule::unique('users', 'phone')->ignore($user->id)],
                'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            ], [
                'phone.required' => 'Nomor HP wajib diisi.',
                'phone.regex' => 'Nomor HP hanya boleh berisi angka dan harus diawali 0 atau 62.',
                'phone.unique' => 'Nomor HP sudah digunakan.',
                'email.required' => 'Email wajib diisi.',
                'email.email' => 'Format email tidak valid.',
                'email.unique' => 'Email sudah digunakan.',
            ]);
        } catch (ValidationException $exception) {
            $this->dispatch('tenant-toast', type: 'error', message: $exception->validator->errors()->first());

            throw $exception;
        }

        DB::transaction(function () use ($user, $tenant, $validated): void {
            $tenant->update(['phone' => $validated['phone'], 'email' => $validated['email']]);
            $user->update(['phone' => $tenant->fresh()->phone, 'email' => $validated['email']]);
        });

        $this->dispatch('tenant-toast', type: 'success', message: 'Kontak berhasil diperbarui.');
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
