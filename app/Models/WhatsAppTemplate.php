<?php

namespace App\Models;

use Database\Factories\WhatsAppTemplateFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WhatsAppTemplate extends Model
{
    /** @use HasFactory<WhatsAppTemplateFactory> */
    use HasFactory;

    protected $table = 'whatsapp_templates';

    protected $fillable = [
        'template_name',
        'display_name',
        'category',
        'language',
        'content',
        'variables',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'variables' => 'array',
            'is_active' => 'boolean',
        ];
    }

    /** @param array<string, scalar|null> $values */
    public function renderPreview(array $values): string
    {
        return str_replace(
            array_map(fn (string $key): string => '{{'.$key.'}}', array_keys($values)),
            array_map(fn ($value): string => (string) $value, array_values($values)),
            $this->content,
        );
    }

    public function broadcasts(): HasMany
    {
        return $this->hasMany(Broadcast::class, 'whatsapp_template_id');
    }
}
