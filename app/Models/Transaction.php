<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class Transaction extends Model
{
    protected $fillable = [
        'type',
        'name',
        'amount',
        'note',
        'transaction_date',
        'source',
    ];

    protected $casts = [
        'amount'           => 'decimal:2',
        'transaction_date' => 'date',
    ];

    // ── Scopes ──────────────────────────────────────────────────

    public function scopeIncome(Builder $query): Builder
    {
        return $query->where('type', 'income');
    }

    public function scopeExpense(Builder $query): Builder
    {
        return $query->where('type', 'expense');
    }

    public function scopeThisMonth(Builder $query): Builder
    {
        return $query->whereYear('transaction_date', now()->year)
                     ->whereMonth('transaction_date', now()->month);
    }

    public function scopeByMonth(Builder $query, int $year, int $month): Builder
    {
        return $query->whereYear('transaction_date', $year)
                     ->whereMonth('transaction_date', $month);
    }

    public function scopeByYear(Builder $query, int $year): Builder
    {
        return $query->whereYear('transaction_date', $year);
    }

    // ── Accessors ────────────────────────────────────────────────

    public function getFormattedAmountAttribute(): string
    {
        return 'Rp ' . number_format((float) $this->amount, 0, ',', '.');
    }

    public function getFormattedDateAttribute(): string
    {
        return $this->transaction_date
            ? Carbon::parse($this->transaction_date)->locale('id')->translatedFormat('d F Y')
            : '-';
    }
}
