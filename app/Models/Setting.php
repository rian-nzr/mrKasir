<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Setting extends Model
{
    //
    use HasFactory;
    protected $fillable = ['store_id', 'shop', 'address', 'phone','name_printer', 'image', 'print_via_mobile'];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * Get setting for specific store
     */
    public static function forStore($storeId = null): ?Setting
    {
        if (!$storeId) {
            $user = auth()->user();
            if ($user->hasRole('super_admin')) {
                $storeId = session('selected_store_id');
                if (!$storeId) {
                    // Fallback: ambil setting dari store manapun untuk super admin
                    return self::first();
                }
            } else {
                $storeId = $user->store_id;
            }
        }
        
        return self::where('store_id', $storeId)->first();
    }

    /**
     * Get current store setting based on user context
     */
    public static function current(): ?Setting
    {
        return self::forStore();
    }
}
