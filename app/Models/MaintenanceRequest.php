<?php

namespace App\Models;

use App\Notifications\TenantActivityNotification;
use App\Services\TenantNotificationService;
use Database\Factories\MaintenanceRequestFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaintenanceRequest extends Model
{
    /** @use HasFactory<MaintenanceRequestFactory> */
    use HasFactory;

    protected $fillable = ['tenant_id', 'room_id', 'title', 'description', 'priority', 'status', 'photo', 'cost', 'expense_id', 'completed_at', 'resolution_notes'];

    protected static function booted(): void
    {
        static::created(fn (MaintenanceRequest $maintenanceRequest) => ActivityLog::record('maintenance.reported', $maintenanceRequest));
        static::updated(function (MaintenanceRequest $maintenanceRequest): void {
            ActivityLog::record('maintenance.updated', $maintenanceRequest, ['changes' => array_keys($maintenanceRequest->getChanges())]);

            if ($maintenanceRequest->wasChanged('status') && $maintenanceRequest->tenant) {
                $statusText = match ($maintenanceRequest->status) {
                    'in_progress' => 'sedang diproses',
                    'completed' => 'telah selesai',
                    'cancelled' => 'dibatalkan',
                    default => 'berstatus '.$maintenanceRequest->status,
                };

                app(TenantNotificationService::class)->notifyTenant(
                    $maintenanceRequest->tenant,
                    new TenantActivityNotification(
                        'maintenance_status_updated',
                        'Status perbaikan diperbarui',
                        "Laporan perbaikan '{$maintenanceRequest->title}' {$statusText}.",
                        route('tenant.maintenance.index')
                    )
                );
            }
        });
    }

    protected function casts(): array
    {
        return ['cost' => 'integer', 'completed_at' => 'datetime'];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function expense(): BelongsTo
    {
        return $this->belongsTo(Expense::class);
    }
}
