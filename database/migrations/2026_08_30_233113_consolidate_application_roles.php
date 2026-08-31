<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $tableNames = config('permission.table_names');
        $columnNames = config('permission.column_names');
        $rolePivotKey = $columnNames['role_pivot_key'] ?? 'role_id';

        DB::transaction(function () use ($tableNames, $rolePivotKey): void {
            $legacyRoleIds = DB::table($tableNames['roles'])
                ->where('guard_name', 'web')
                ->whereIn('name', ['admin', 'staff'])
                ->pluck('id');

            if ($legacyRoleIds->isEmpty()) {
                return;
            }

            $ownerRoleId = DB::table($tableNames['roles'])
                ->where('guard_name', 'web')
                ->where('name', 'owner')
                ->value('id');

            if ($ownerRoleId === null) {
                $ownerRoleId = DB::table($tableNames['roles'])->insertGetId([
                    'name' => 'owner',
                    'guard_name' => 'web',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table($tableNames['model_has_roles'])
                ->whereIn($rolePivotKey, $legacyRoleIds)
                ->get()
                ->map(function (object $assignment) use ($ownerRoleId, $rolePivotKey): array {
                    $attributes = (array) $assignment;
                    $attributes[$rolePivotKey] = $ownerRoleId;

                    return $attributes;
                })
                ->chunk(500)
                ->each(fn ($assignments) => DB::table($tableNames['model_has_roles'])->insertOrIgnore($assignments->all()));

            DB::table($tableNames['role_has_permissions'])
                ->whereIn($rolePivotKey, $legacyRoleIds)
                ->get()
                ->map(function (object $assignment) use ($ownerRoleId, $rolePivotKey): array {
                    $attributes = (array) $assignment;
                    $attributes[$rolePivotKey] = $ownerRoleId;

                    return $attributes;
                })
                ->chunk(500)
                ->each(fn ($assignments) => DB::table($tableNames['role_has_permissions'])->insertOrIgnore($assignments->all()));

            DB::table($tableNames['roles'])->whereIn('id', $legacyRoleIds)->delete();
        });

        app('cache')
            ->store(config('permission.cache.store') !== 'default' ? config('permission.cache.store') : null)
            ->forget(config('permission.cache.key'));
    }

    /**
     * Legacy role assignments cannot be reconstructed after consolidation.
     */
    public function down(): void {}
};
