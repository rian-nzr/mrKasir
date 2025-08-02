<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Scopes\StoreScope;

class CashierShift extends Model
{
    use HasFactory;

    protected $fillable = [
        'store_id',
        'user_id',
        'shift_number',
        'status',
        'opened_at',
        'closed_at',
        'opening_cash',
        'closing_cash',
        'expected_cash',
        'cash_difference',
        'location',
        'notes',
        'closing_notes',
        'total_sales',
        'total_transactions',
        'total_discounts',
        'cash_out_amount',
        'cash_out_notes',
        'payment_summary',
        'shift_summary',
    ];

    protected $casts = [
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
        'opening_cash' => 'decimal:2',
        'closing_cash' => 'decimal:2',
        'expected_cash' => 'decimal:2', 
        'cash_difference' => 'decimal:2',
        'total_sales' => 'decimal:2',
        'total_discounts' => 'decimal:2',
        'cash_out_amount' => 'decimal:2',
        'payment_summary' => 'array',
        'shift_summary' => 'array',
    ];

    protected static function booted()
    {
        static::addGlobalScope(new StoreScope);
        
        static::creating(function ($shift) {
            if (!$shift->shift_number) {
                $shift->shift_number = static::generateShiftNumber($shift->store_id);
            }
        });
    }

    // Relationships
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'cashier_shift_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'cashier_shift_id');
    }

    public function cashOuts(): HasMany
    {
        return $this->hasMany(CashOut::class, 'cashier_shift_id');
    }

    // Status constants
    const STATUS_OPEN = 'open';
    const STATUS_CLOSED = 'closed';
    const STATUS_SUSPENDED = 'suspended';

    // Helper methods
    public function isOpen(): bool
    {
        return $this->status === self::STATUS_OPEN;
    }

    public function isClosed(): bool
    {
        return $this->status === self::STATUS_CLOSED;
    }

    public function canBeOpened(): bool
    {
        return $this->status === null || $this->status === self::STATUS_SUSPENDED;
    }

    public function canBeClosed(): bool
    {
        return $this->status === self::STATUS_OPEN;
    }

    public function open(float $openingCash, ?string $location = null, ?string $notes = null): void
    {
        $this->update([
            'status' => self::STATUS_OPEN,
            'opened_at' => now(),
            'opening_cash' => $openingCash,
            'location' => $location,
            'notes' => $notes,
        ]);
    }

    public function close(float $closingCash, ?string $closingNotes = null): void
    {
        $this->calculateShiftSummary();
        
        $expectedCash = $this->opening_cash + $this->getCashSalesTotal() - $this->cash_out_amount;
        $difference = $closingCash - $expectedCash;

        $this->update([
            'status' => self::STATUS_CLOSED,
            'closed_at' => now(),
            'closing_cash' => $closingCash,
            'expected_cash' => $expectedCash,
            'cash_difference' => $difference,
            'closing_notes' => $closingNotes,
        ]);
    }

    public function suspend(?string $notes = null): void
    {
        $this->update([
            'status' => self::STATUS_SUSPENDED,
            'notes' => $notes,
        ]);
    }

    public function getCashSalesTotal(): float
    {
        return $this->orders()
            ->whereHas('paymentMethod', function ($query) {
                $query->where('is_cash', true);
            })
            ->sum('total_price');
    }

    public function getNonCashSalesTotal(): float
    {
        return $this->orders()
            ->whereHas('paymentMethod', function ($query) {
                $query->where('is_cash', false);
            })
            ->sum('total_price');
    }

    public function getTotalSales(): float
    {
        return $this->orders()->sum('total_price');
    }

    public function getTotalTransactions(): int
    {
        return $this->orders()->count();
    }

    public function getTotalDiscounts(): float
    {
        // If orders table doesn't have discount_amount field,
        // we could calculate from order_products if needed
        // For now, return 0 as discount functionality isn't implemented
        return 0.0;
    }

    public function getPaymentSummary(): array
    {
        $summary = [];
        
        // Get payment methods summary directly from orders
        $paymentMethods = $this->orders()
            ->join('payment_methods as pm', 'orders.payment_method_id', '=', 'pm.id')
            ->select(
                'pm.name',
                'pm.is_cash',
                'pm.is_ewallet',
                \DB::raw('COUNT(*) as transaction_count'),
                \DB::raw('SUM(orders.total_price) as total_amount')
            )
            ->groupBy('pm.id', 'pm.name', 'pm.is_cash', 'pm.is_ewallet')
            ->withoutGlobalScopes() // Remove global scopes to avoid ambiguous column issues
            ->where('orders.cashier_shift_id', $this->id) // Explicit filter by shift ID
            ->get();

        foreach ($paymentMethods as $method) {
            $type = 'other';
            if ($method->is_cash) {
                $type = 'cash';
            } elseif ($method->is_ewallet) {
                $type = 'ewallet';
            }
            
            $summary[] = [
                'name' => $method->name,
                'type' => $type,
                'count' => $method->transaction_count,
                'amount' => (float) $method->total_amount,
            ];
        }

        return $summary;
    }

    private function calculateShiftSummary(): void
    {
        $totalSales = $this->getTotalSales();
        $totalTransactions = $this->getTotalTransactions();
        $totalDiscounts = $this->getTotalDiscounts();
        $paymentSummary = $this->getPaymentSummary();
        $cashOutTotal = $this->cashOuts()->sum('amount');

        $this->update([
            'total_sales' => $totalSales,
            'total_transactions' => $totalTransactions,
            'total_discounts' => $totalDiscounts,
            'cash_out_amount' => $cashOutTotal,
            'payment_summary' => $paymentSummary,
            'shift_summary' => [
                'gross_sales' => $totalSales,
                'net_sales' => $totalSales - $totalDiscounts,
                'cash_sales' => $this->getCashSalesTotal(),
                'non_cash_sales' => $this->getNonCashSalesTotal(),
                'total_transactions' => $totalTransactions,
                'average_transaction' => $totalTransactions > 0 ? $totalSales / $totalTransactions : 0,
                'cash_out' => $cashOutTotal,
            ]
        ]);
    }

    public static function generateShiftNumber(int $storeId): string
    {
        $date = now()->format('Ymd');
        $maxAttempts = 10;
        $attempt = 0;

        while ($attempt < $maxAttempts) {
            // Get the next sequence number with database locking
            $lastShift = static::withoutGlobalScope(StoreScope::class)
                ->where('store_id', $storeId)
                ->where('shift_number', 'like', $date . '%')
                ->orderBy('shift_number', 'desc')
                ->lockForUpdate()
                ->first();

            $sequence = 1;
            if ($lastShift) {
                $lastSequence = (int) substr($lastShift->shift_number, -3);
                $sequence = $lastSequence + 1;
            }

            $shiftNumber = $date . str_pad($sequence, 3, '0', STR_PAD_LEFT);

            // Check if this shift number already exists
            $exists = static::withoutGlobalScope(StoreScope::class)
                ->where('shift_number', $shiftNumber)
                ->exists();

            if (!$exists) {
                return $shiftNumber;
            }

            $attempt++;
            // Small random delay to avoid thundering herd
            usleep(rand(10000, 50000)); // 10-50ms
        }

        // Fallback: use timestamp-based unique number
        return $date . str_pad(time() % 1000, 3, '0', STR_PAD_LEFT);
    }

    public static function getCurrentShift(?int $userId = null): ?self
    {
        $query = static::where('status', self::STATUS_OPEN);
        
        if ($userId) {
            $query->where('user_id', $userId);
        }
        
        return $query->first();
    }

    public static function getActiveShiftForUser(int $userId): ?self
    {
        return static::where('user_id', $userId)
            ->where('status', self::STATUS_OPEN)
            ->first();
    }

    public function getFormattedShiftNumber(): string
    {
        return 'SHIFT-' . $this->shift_number;
    }

    public function getDurationAttribute(): ?string
    {
        if (!$this->opened_at) {
            return null;
        }

        $end = $this->closed_at ?? now();
        $duration = $this->opened_at->diff($end);

        return $duration->format('%H:%I:%S');
    }

    public function getStatusDisplayAttribute(): string
    {
        return match($this->status) {
            self::STATUS_OPEN => 'Terbuka',
            self::STATUS_CLOSED => 'Ditutup',
            self::STATUS_SUSPENDED => 'Ditangguhkan',
            default => 'Tidak Diketahui'
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            self::STATUS_OPEN => 'success',
            self::STATUS_CLOSED => 'gray',
            self::STATUS_SUSPENDED => 'warning',
            default => 'gray'
        };
    }

    // Scopes
    public function scopeOpen($query)
    {
        return $query->where('status', self::STATUS_OPEN);
    }

    public function scopeClosed($query)
    {
        return $query->where('status', self::STATUS_CLOSED);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('opened_at', today());
    }

    public function scopeThisWeek($query)
    {
        return $query->whereBetween('opened_at', [
            now()->startOfWeek(),
            now()->endOfWeek()
        ]);
    }

    public function scopeThisMonth($query)
    {
        return $query->whereMonth('opened_at', now()->month)
            ->whereYear('opened_at', now()->year);
    }
}
