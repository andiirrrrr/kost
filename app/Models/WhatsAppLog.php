<?php

namespace App\Models;

use App\Enums\WhatsAppStatus;
use Database\Factories\WhatsAppLogFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsAppLog extends Model
{
    /** @use HasFactory<WhatsAppLogFactory> */
    use HasFactory;

    protected $table = 'whatsapp_logs';

    protected $fillable = [
        'tenant_id',
        'invoice_id',
        'broadcast_id',
        'phone',
        'template_name',
        'parameters',
        'message_id',
        'deduplication_key',
        'status',
        'error_message',
        'attempts',
        'sent_at',
        'delivered_at',
        'read_at',
        'failed_at',
    ];

    protected function casts(): array
    {
        return [
            'parameters' => 'array',
            'status' => WhatsAppStatus::class,
            'sent_at' => 'datetime',
            'delivered_at' => 'datetime',
            'read_at' => 'datetime',
            'failed_at' => 'datetime',
            'attempts' => 'integer',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function broadcast(): BelongsTo
    {
        return $this->belongsTo(Broadcast::class);
    }
}
