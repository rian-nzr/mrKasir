<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Scopes\StoreScope;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'store_id',
        'user_id',
        'type',
        'amount',
        'admin_luar',
        'admin_dalam',
        'keterangan',
        'sumber_dana_id',
        'tujuan',
        'terima_dana',
        'admin',
        'jenis_transaksi',
        'sumber',
        'modal',
        'harga_jual',
        'status',
        'financial_impact',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'admin_luar' => 'decimal:2',
        'admin_dalam' => 'decimal:2',
        'terima_dana' => 'decimal:2',
        'admin' => 'decimal:2',
        'modal' => 'decimal:2',
        'harga_jual' => 'decimal:2',
        'financial_impact' => 'array',
    ];

    protected static function booted()
    {
        static::addGlobalScope(new StoreScope);
    }

    // Relationships
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sumberDana(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class, 'sumber_dana_id');
    }

    // Helper methods
    public function getTotalProfitAttribute(): float
    {
        return match($this->type) {
            'transfer', 'tarik_tunai' => (float)($this->admin_luar ?? 0) + (float)($this->admin_dalam ?? 0),
            'jasa_transfer' => (float)($this->admin ?? 0),
            'mode_pulsa' => (float)($this->harga_jual ?? 0) - (float)($this->modal ?? 0),
            default => 0
        };
    }

    public function getDisplayAmountAttribute(): string
    {
        $amount = match($this->type) {
            'transfer', 'tarik_tunai' => (float)($this->amount ?? 0),
            'jasa_transfer' => (float)($this->terima_dana ?? 0),
            'mode_pulsa' => (float)($this->modal ?? 0),
            default => (float)($this->amount ?? 0)
        };
        
        return 'Rp ' . number_format($amount, 0, ',', '.');
    }

    public function getTypeDisplayAttribute(): string
    {
        return match($this->type) {
            'transfer' => 'Transfer',
            'tarik_tunai' => 'Tarik Tunai',
            'jasa_transfer' => 'Jasa Transfer',
            'mode_pulsa' => 'Mode Pulsa',
            default => ucfirst(str_replace('_', ' ', $this->type ?? 'unknown'))
        };
    }

    public function getFinancialImpactDisplayAttribute(): string
    {
        $state = $this->financial_impact;
        
        if (empty($state) || !is_array($state)) {
            return 'Tidak ada dampak finansial tercatat';
        }
        
        $formatted = [];
        
        // Format profit information
        if (isset($state['profit'])) {
            $formatted[] = 'Keuntungan: Rp ' . number_format($state['profit'], 0, ',', '.');
        }
        
        // Format admin_dalam information
        if (isset($state['admin_dalam']) && is_array($state['admin_dalam'])) {
            $admin = $state['admin_dalam'];
            if (isset($admin['amount'])) {
                $formatted[] = 'Admin Dalam: Rp ' . number_format($admin['amount'], 0, ',', '.');
            }
        }
        
        // Format sumber_dana information
        if (isset($state['sumber_dana']) && is_array($state['sumber_dana'])) {
            $sumber = $state['sumber_dana'];
            $formatted[] = 'Sumber Dana:';
            if (isset($sumber['old_balance'])) {
                $formatted[] = '• Saldo Lama: Rp ' . number_format($sumber['old_balance'], 0, ',', '.');
            }
            if (isset($sumber['change'])) {
                $formatted[] = '• Perubahan: Rp ' . number_format($sumber['change'], 0, ',', '.');
            }
            if (isset($sumber['new_balance'])) {
                $formatted[] = '• Saldo Baru: Rp ' . number_format($sumber['new_balance'], 0, ',', '.');
            }
        }
        
        return implode("\n", $formatted);
    }

    // Scope methods
    public function scopeFindByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }
}
