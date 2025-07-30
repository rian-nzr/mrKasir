<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Scopes\StoreScope;

class PaymentMethod extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'image',
        'is_cash',
        'is_ewallet',
        'balance',
        'account_number',
        'account_name',
        'bank_name',
        'description',
        'is_active',
        'store_id',
    ];

    protected $casts = [
        'is_cash' => 'boolean',
        'is_ewallet' => 'boolean',
        'is_active' => 'boolean',
        'balance' => 'decimal:2',
    ];

    protected $appends = ['image_url'];

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(PaymentMethodTransaction::class, 'from_payment_method_id');
    }

    public function incomingTransactions(): HasMany
    {
        return $this->hasMany(PaymentMethodTransaction::class, 'to_payment_method_id');
    }

    public function allTransactions()
    {
        return PaymentMethodTransaction::query()
            ->where(function($query) {
                $query->where('from_payment_method_id', $this->id)
                      ->orWhere('to_payment_method_id', $this->id);
            })
            ->orderBy('created_at', 'desc');
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function getImageUrlAttribute()
    {
        return $this->image ? url('storage/'. $this->image) : null;
    }

    public function getFormattedBalanceAttribute()
    {
        return 'Rp ' . number_format((float) $this->balance, 0, ',', '.');
    }

    public function getPaymentTypeAttribute()
    {
        if ($this->is_cash) {
            return 'Cash';
        } elseif ($this->is_ewallet) {
            return 'E-Wallet';
        } else {
            return 'Bank Transfer';
        }
    }

    public function getAccountInfoAttribute()
    {
        if ($this->is_ewallet || !$this->is_cash) {
            $info = [];
            if ($this->account_number) {
                $info[] = $this->account_number;
            }
            if ($this->account_name) {
                $info[] = $this->account_name;
            }
            if ($this->bank_name) {
                $info[] = $this->bank_name;
            }
            return implode(' - ', $info);
        }
        return null;
    }

    public function updateBalance($amount, $type = 'add')
    {
        if ($type === 'add') {
            $this->increment('balance', $amount);
        } elseif ($type === 'subtract') {
            $this->decrement('balance', $amount);
        }
        return $this->fresh();
    }

    public function transferTo(PaymentMethod $targetPaymentMethod, $amount, $description = null)
    {
        if ($this->balance < $amount) {
            throw new \Exception('Saldo tidak mencukupi untuk transfer');
        }

        if (!$this->is_active || !$targetPaymentMethod->is_active) {
            throw new \Exception('Salah satu payment method tidak aktif');
        }

        $baseReferenceNumber = PaymentMethodTransaction::generateReferenceNumber();
        
        \DB::transaction(function () use ($targetPaymentMethod, $amount, $description, $baseReferenceNumber) {
            // Record transfer out
            PaymentMethodTransaction::create([
                'from_payment_method_id' => $this->id,
                'to_payment_method_id' => $targetPaymentMethod->id,
                'type' => 'transfer_out',
                'amount' => $amount,
                'balance_before' => $this->balance,
                'balance_after' => $this->balance - $amount,
                'description' => $description ?? "Transfer ke {$targetPaymentMethod->name}",
                'reference_number' => $baseReferenceNumber . '-OUT',
                'created_by' => auth()->id(),
            ]);

            // Record transfer in
            PaymentMethodTransaction::create([
                'from_payment_method_id' => $this->id,
                'to_payment_method_id' => $targetPaymentMethod->id,
                'type' => 'transfer_in',
                'amount' => $amount,
                'balance_before' => $targetPaymentMethod->balance,
                'balance_after' => $targetPaymentMethod->balance + $amount,
                'description' => $description ?? "Transfer dari {$this->name}",
                'reference_number' => $baseReferenceNumber . '-IN',
                'created_by' => auth()->id(),
            ]);

            // Update balances
            $this->decrement('balance', $amount);
            $targetPaymentMethod->increment('balance', $amount);
        });

        return $baseReferenceNumber;
    }

    public function recordTransaction($type, $amount, $description = null)
    {
        $balanceBefore = $this->balance;
        
        if ($type === 'topup') {
            $this->increment('balance', $amount);
        } elseif ($type === 'withdraw') {
            $this->decrement('balance', $amount);
        }
        
        $this->refresh();
        
        return PaymentMethodTransaction::create([
            'from_payment_method_id' => $type === 'withdraw' ? $this->id : null,
            'to_payment_method_id' => $type === 'topup' ? $this->id : null,
            'type' => $type,
            'amount' => $amount,
            'balance_before' => $balanceBefore,
            'balance_after' => $this->balance,
            'description' => $description ?? ucfirst($type) . " saldo {$this->name}",
            'reference_number' => PaymentMethodTransaction::generateReferenceNumber(),
            'created_by' => auth()->id(),
        ]);
    }

    public static function getTotalBalance()
    {
        return static::where('is_active', true)->sum('balance');
    }

    public static function getFormattedTotalBalance()
    {
        return 'Rp ' . number_format((float) static::getTotalBalance(), 0, ',', '.');
    }

    public static function getBalanceByType()
    {
        return [
            'cash' => static::where('is_active', true)->where('is_cash', true)->sum('balance'),
            'ewallet' => static::where('is_active', true)->where('is_ewallet', true)->sum('balance'),
            'transfer' => static::where('is_active', true)
                ->where('is_cash', false)
                ->where('is_ewallet', false)
                ->sum('balance'),
        ];
    }

    protected static function booted(): void
    {
        parent::boot();
        
        // Apply global scope for multi-store functionality
        static::addGlobalScope(new StoreScope);
    }
}
