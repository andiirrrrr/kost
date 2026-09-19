<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class TenantAccountService
{
    /** @return array{user: User, temporary_password: string} */
    public function create(Tenant $tenant, string $email, ?string $temporaryPassword = null): array
    {
        if ($tenant->user_id) {
            throw new InvalidArgumentException('Penghuni ini sudah memiliki akun.');
        }

        $password = $temporaryPassword ?: Str::password(16);

        $user = DB::transaction(function () use ($tenant, $email, $password): User {
            $lockedTenant = Tenant::query()->lockForUpdate()->findOrFail($tenant->id);

            if ($lockedTenant->user_id) {
                throw new InvalidArgumentException('Penghuni ini sudah memiliki akun.');
            }

            $user = User::create([
                'name' => $lockedTenant->name,
                'email' => $email,
                'phone' => $lockedTenant->phone,
                'password' => $password,
            ]);
            $user->assignRole('tenant');
            $lockedTenant->update(['user_id' => $user->id, 'email' => $email]);

            ActivityLog::record('tenant.account_created', $user, ['tenant_id' => $lockedTenant->id]);

            return $user;
        });

        return ['user' => $user, 'temporary_password' => $password];
    }

    /** @return array{user: User, password: string} */
    public function resetPassword(Tenant $tenant, ?string $newPassword = null): array
    {
        if (! $tenant->user_id) {
            throw new InvalidArgumentException('Penghuni ini belum memiliki akun.');
        }

        $password = filled($newPassword) ? (string) $newPassword : Str::password(12, letters: true, numbers: true, symbols: false);

        $user = DB::transaction(function () use ($tenant, $password): User {
            $lockedTenant = Tenant::query()->lockForUpdate()->findOrFail($tenant->id);

            if (! $lockedTenant->user_id) {
                throw new InvalidArgumentException('Penghuni ini belum memiliki akun.');
            }

            $user = User::query()->lockForUpdate()->findOrFail($lockedTenant->user_id);
            $user->update(['password' => $password]);

            ActivityLog::record('account.password_reset', $user, ['tenant_id' => $lockedTenant->id]);

            return $user;
        });

        return ['user' => $user, 'password' => $password];
    }
}
