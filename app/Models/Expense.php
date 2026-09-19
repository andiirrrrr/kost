<?php

namespace App\Models;

use App\Enums\ExpenseCategory;
use Database\Factories\ExpenseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Expense extends Model
{
    /** @use HasFactory<ExpenseFactory> */
    use HasFactory, SoftDeletes;

    protected static function booted(): void
    {
        static::created(fn (Expense $expense) => ActivityLog::record('expense.created', $expense, ['amount' => $expense->amount, 'category' => $expense->category->value]));
        static::updated(fn (Expense $expense) => ActivityLog::record('expense.updated', $expense, ['changes' => array_keys($expense->getChanges())]));
        static::deleted(fn (Expense $expense) => ActivityLog::record('expense.deleted', $expense));
    }

    protected $fillable = [
        'category',
        'description',
        'amount',
        'expense_date',
        'proof',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'category' => ExpenseCategory::class,
            'amount' => 'integer',
            'expense_date' => 'date',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
