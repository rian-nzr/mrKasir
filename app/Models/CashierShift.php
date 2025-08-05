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
        'opening_balance_snapshot',
        'opening_total_balance',
        'closing_cash',
        'closing_balance_snapshot',
        'closing_total_balance',
        'calculated_cash_flow',
        'expected_total_balance',
        'total_balance_difference',
        'physical_cash_count',
        'cash_denomination_count',
        'cash_counting_difference',
        'expected_cash',
        'cash_difference',
        'location',
        'notes',
        'closing_notes',
        'total_sales',
        'total_transactions',
        'total_discounts',
        'total_profit',
        'transaction_profit',
        'order_profit',
        'cash_out_amount',
        'cash_out_notes',
        'payment_summary',
        'shift_summary',
    ];

    protected $casts = [
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
        'opening_cash' => 'decimal:2',
        'opening_balance_snapshot' => 'array',
        'opening_total_balance' => 'decimal:2',
        'closing_cash' => 'decimal:2',
        'closing_balance_snapshot' => 'array',
        'closing_total_balance' => 'decimal:2',
        'calculated_cash_flow' => 'decimal:2',
        'expected_total_balance' => 'decimal:2',
        'total_balance_difference' => 'decimal:2',
        'physical_cash_count' => 'decimal:2',
        'cash_denomination_count' => 'array',
        'cash_counting_difference' => 'decimal:2',
        'expected_cash' => 'decimal:2', 
        'cash_difference' => 'decimal:2',
        'total_sales' => 'decimal:2',
        'total_discounts' => 'decimal:2',
        'total_profit' => 'decimal:2',
        'transaction_profit' => 'decimal:2',
        'order_profit' => 'decimal:2',
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

    public function getTotalTransactionProfit(): float
    {
        return $this->transactions()->sum(\DB::raw('
            CASE 
                WHEN type IN ("transfer", "tarik_tunai") THEN COALESCE(admin_luar, 0) + COALESCE(admin_dalam, 0)
                WHEN type = "jasa_transfer" THEN COALESCE(admin, 0)
                WHEN type = "mode_pulsa" THEN COALESCE(harga_jual, 0) - COALESCE(modal, 0) + COALESCE(admin, 0)
                ELSE 0
            END
        '));
    }

    public function getTotalProfit(): float
    {
        // Profit dari orders (selling price - cost price)
        $orderProfit = $this->orders()
            ->join('order_products', 'orders.id', '=', 'order_products.order_id')
            ->join('products', 'order_products.product_id', '=', 'products.id')
            ->withoutGlobalScopes() // Remove global scopes to avoid ambiguous column issues
            ->where('orders.cashier_shift_id', $this->id) // Explicit filter by shift ID
            ->sum(\DB::raw('order_products.quantity * (order_products.unit_price - COALESCE(products.cost_price, 0))'));

        // Profit dari transactions (biaya admin, dll)
        $transactionProfit = $this->getTotalTransactionProfit();

        return $orderProfit + $transactionProfit;
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
        $totalProfit = $this->getTotalProfit();
        $transactionProfit = $this->getTotalTransactionProfit();
        $orderProfit = $totalProfit - $transactionProfit;
        $paymentSummary = $this->getPaymentSummary();
        $cashOutTotal = $this->cashOuts()->sum('amount');

        $this->update([
            'total_sales' => $totalSales,
            'total_transactions' => $totalTransactions,
            'total_discounts' => $totalDiscounts,
            'total_profit' => $totalProfit,
            'transaction_profit' => $transactionProfit,
            'order_profit' => $orderProfit,
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
                'total_profit' => $totalProfit,
                'transaction_profit' => $transactionProfit,
                'order_profit' => $orderProfit,
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

    // ====== BALANCE TRACKING METHODS ======
    
    /**
     * Capture snapshot of all payment method balances at shift opening
     * Note: Only cash balance is set to opening_cash input, others remain as system values
     */
    public function captureOpeningBalanceSnapshot(): array
    {
        $balances = PaymentMethod::where('store_id', $this->store_id)
            ->select('id', 'name', 'balance', 'is_cash', 'is_ewallet')
            ->get()
            ->map(function ($method) {
                // For cash method, use the opening_cash input instead of system balance
                $balance = $method->is_cash ? (float) $this->opening_cash : (float) $method->balance;
                
                return [
                    'id' => $method->id,
                    'name' => $method->name,
                    'balance' => $balance,
                    'is_cash' => $method->is_cash,
                    'is_ewallet' => $method->is_ewallet,
                    'type' => $method->is_cash ? 'cash' : ($method->is_ewallet ? 'ewallet' : 'other'),
                ];
            })
            ->toArray();

        $totalBalance = array_sum(array_column($balances, 'balance'));

        $this->update([
            'opening_balance_snapshot' => $balances,
            'opening_total_balance' => $totalBalance,
        ]);

        return $balances;
    }

    /**
     * Capture snapshot of all payment method balances at shift closing
     */
    public function captureClosingBalanceSnapshot(): array
    {
        $balances = PaymentMethod::where('store_id', $this->store_id)
            ->select('id', 'name', 'balance', 'is_cash', 'is_ewallet')
            ->get()
            ->map(function ($method) {
                return [
                    'id' => $method->id,
                    'name' => $method->name,
                    'balance' => (float) $method->balance,
                    'is_cash' => $method->is_cash,
                    'is_ewallet' => $method->is_ewallet,
                    'type' => $method->is_cash ? 'cash' : ($method->is_ewallet ? 'ewallet' : 'other'),
                ];
            })
            ->toArray();

        $totalBalance = array_sum(array_column($balances, 'balance'));

        $this->update([
            'closing_balance_snapshot' => $balances,
            'closing_total_balance' => $totalBalance,
        ]);

        return $balances;
    }

    /**
     * Calculate expected total balance based on opening balance + cash flows
     */
    public function calculateExpectedTotalBalance(): float
    {
        $openingBalance = $this->opening_total_balance ?? 0;
        $cashFlow = $this->calculateTotalCashFlow();
        
        $expectedBalance = $openingBalance + $cashFlow;
        
        $this->update([
            'calculated_cash_flow' => $cashFlow,
            'expected_total_balance' => $expectedBalance,
        ]);
        
        return $expectedBalance;
    }

    /**
     * Calculate total cash flow during shift (sales, transactions, cash outs)
     */
    public function calculateTotalCashFlow(): float
    {
        // Sales income (all payment methods)
        $salesIncome = $this->getTotalSales();
        
        // Transaction profits (admin fees, etc)
        $transactionProfits = $this->getTotalTransactionProfit();
        
        // Cash outs (expenses)
        $cashOuts = $this->cash_out_amount ?? 0;
        
        // Total cash flow = income - expenses
        return $salesIncome + $transactionProfits - $cashOuts;
    }

    /**
     * Calculate balance differences between expected and actual
     */
    public function calculateBalanceDifferences(): array
    {
        $expectedTotal = $this->expected_total_balance ?? $this->calculateExpectedTotalBalance();
        $actualTotal = $this->closing_total_balance ?? 0;
        $totalDifference = $actualTotal - $expectedTotal;

        // Cash counting difference (physical vs system)
        $systemCashBalance = $this->getSystemCashBalance();
        $physicalCashCount = $this->physical_cash_count ?? 0;
        $cashCountingDifference = $physicalCashCount - $systemCashBalance;

        $this->update([
            'total_balance_difference' => $totalDifference,
            'cash_counting_difference' => $cashCountingDifference,
        ]);

        return [
            'total_difference' => $totalDifference,
            'cash_counting_difference' => $cashCountingDifference,
            'expected_total' => $expectedTotal,
            'actual_total' => $actualTotal,
            'system_cash' => $systemCashBalance,
            'physical_cash' => $physicalCashCount,
        ];
    }

    /**
     * Get current system cash balance
     */
    public function getSystemCashBalance(): float
    {
        $cashMethod = PaymentMethod::where('store_id', $this->store_id)
            ->where('is_cash', true)
            ->first();
            
        return $cashMethod ? (float) $cashMethod->balance : 0;
    }

    /**
     * Set physical cash count with denomination breakdown
     */
    public function setPhysicalCashCount(float $totalCount, array $denominations = []): void
    {
        $this->update([
            'physical_cash_count' => $totalCount,
            'cash_denomination_count' => $denominations,
        ]);
    }

    /**
     * Get balance comparison report
     */
    public function getBalanceComparisonReport(): array
    {
        $opening = $this->opening_balance_snapshot ?? [];
        $closing = $this->closing_balance_snapshot ?? [];
        
        $comparison = [];
        
        foreach ($opening as $openMethod) {
            $closingMethod = collect($closing)->firstWhere('id', $openMethod['id']);
            
            $comparison[] = [
                'id' => $openMethod['id'],
                'name' => $openMethod['name'],
                'type' => $openMethod['type'] ?? ($openMethod['is_cash'] ? 'cash' : ($openMethod['is_ewallet'] ? 'ewallet' : 'other')),
                'opening_balance' => $openMethod['balance'],
                'closing_balance' => $closingMethod['balance'] ?? 0,
                'difference' => ($closingMethod['balance'] ?? 0) - $openMethod['balance'],
                'is_cash' => $openMethod['is_cash'],
                'is_ewallet' => $openMethod['is_ewallet'],
            ];
        }
        
        return $comparison;
    }

    /**
     * Get formatted balance summary for display
     */
    public function getBalanceSummaryForDisplay(): array
    {
        return [
            'opening_total' => 'Rp ' . number_format($this->opening_total_balance ?? 0, 0, ',', '.'),
            'closing_total' => 'Rp ' . number_format($this->closing_total_balance ?? 0, 0, ',', '.'),
            'expected_total' => 'Rp ' . number_format($this->expected_total_balance ?? 0, 0, ',', '.'),
            'cash_flow' => 'Rp ' . number_format($this->calculated_cash_flow ?? 0, 0, ',', '.'),
            'total_difference' => 'Rp ' . number_format($this->total_balance_difference ?? 0, 0, ',', '.'),
            'physical_cash' => 'Rp ' . number_format($this->physical_cash_count ?? 0, 0, ',', '.'),
            'system_cash' => 'Rp ' . number_format($this->getSystemCashBalance(), 0, ',', '.'),
            'cash_difference' => 'Rp ' . number_format($this->cash_counting_difference ?? 0, 0, ',', '.'),
        ];
    }
}
