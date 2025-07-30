<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentMethodTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        "from_payment_method_id",
        "to_payment_method_id", 
        "type",
        "amount",
        "balance_before",
        "balance_after",
        "description",
        "reference_number",
        "created_by",
    ];

    protected $casts = [
        "amount" => "decimal:2",
        "balance_before" => "decimal:2", 
        "balance_after" => "decimal:2",
    ];

    public function fromPaymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class, "from_payment_method_id");
    }

    public function toPaymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class, "to_payment_method_id");
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, "created_by");
    }

    // Helper method to get the main payment method involved in the transaction
    public function getMainPaymentMethodAttribute()
    {
        // For topup and transfer_in, use to_payment_method
        // For withdraw and transfer_out, use from_payment_method
        if (in_array($this->type, ['topup', 'transfer_in'])) {
            return $this->toPaymentMethod;
        } else {
            return $this->fromPaymentMethod;
        }
    }

    public function getFormattedAmountAttribute()
    {
        return "Rp " . number_format((float) $this->amount, 0, ",", ".");
    }

    public function getFormattedBalanceBeforeAttribute()
    {
        return "Rp " . number_format((float) $this->balance_before, 0, ",", ".");
    }

    public function getFormattedBalanceAfterAttribute()
    {
        return "Rp " . number_format((float) $this->balance_after, 0, ",", ".");
    }

    public function getTypeDescriptionAttribute()
    {
        return match($this->type) {
            "topup" => "Top Up",
            "withdraw" => "Withdraw", 
            "transfer_out" => "Transfer Keluar",
            "transfer_in" => "Transfer Masuk",
            default => $this->type,
        };
    }

    public static function generateReferenceNumber(): string
    {
        do {
            $timestamp = now()->format('YmdHis');
            $random = str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
            $referenceNumber = 'TRX' . $timestamp . $random;
        } while (self::where('reference_number', $referenceNumber)->exists());
        
        return $referenceNumber;
    }
}
