<?php

namespace App\Models;

use App\Models\Scopes\StoreScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'birthday',
        'total_price',
        'paid_amount',
        'change_amount',
        'note',
        'payment_method_id',
        'store_id',
        'cashier_shift_id',
    ];

    protected $casts = [
        'total_price' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'change_amount' => 'decimal:2',
    ];

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function orderProducts(): HasMany
    {
        return $this->hasMany(OrderProduct::class, 'order_id');
    }

    public function products(): HasMany
    {
        return $this->orderProducts()->with('product');
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function cashierShift(): BelongsTo
    {
        return $this->belongsTo(CashierShift::class);
    }

    protected static function booted(): void
    {
        parent::boot();
        
        // Re-enable global scope for multi-store functionality
        static::addGlobalScope(new StoreScope);
        
        static::creating(function (Order $order) {
            // Auto assign store_id if not set
            if (!$order->store_id && session('selected_store_id')) {
                $order->store_id = session('selected_store_id');
            }
        });
    }
}




