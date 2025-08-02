<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Scopes\StoreScope;

class CashOut extends Model
{
    use HasFactory;

    protected $fillable = [
        'store_id',
        'cashier_shift_id',
        'user_id',
        'amount',
        'reason',
        'notes',
        'approved_by',
        'approved_at',
        'status',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    protected static function booted()
    {
        static::addGlobalScope(new StoreScope);
    }

    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function cashierShift(): BelongsTo
    {
        return $this->belongsTo(CashierShift::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function approve(int $approvedById, ?string $approvalNotes = null): void
    {
        $this->update([
            'status' => self::STATUS_APPROVED,
            'approved_by' => $approvedById,
            'approved_at' => now(),
            'approval_notes' => $approvalNotes,
        ]);
    }

    public function reject(int $approvedById, string $approvalNotes): void
    {
        $this->update([
            'status' => self::STATUS_REJECTED,
            'approved_by' => $approvedById,
            'approved_at' => now(),
            'approval_notes' => $approvalNotes,
        ]);
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    public function getStatusDisplayAttribute(): string
    {
        return match($this->status) {
            self::STATUS_PENDING => 'Menunggu Persetujuan',
            self::STATUS_APPROVED => 'Disetujui',
            self::STATUS_REJECTED => 'Ditolak',
            default => 'Tidak Diketahui'
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            self::STATUS_PENDING => 'warning',
            self::STATUS_APPROVED => 'success',
            self::STATUS_REJECTED => 'danger',
            default => 'gray'
        };
    }

    public function getFormattedAmountAttribute(): string
    {
        return 'Rp ' . number_format((float) $this->amount, 0, ',', '.');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function scopeRejected($query)
    {
        return $query->where('status', self::STATUS_REJECTED);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }
}
