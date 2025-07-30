<?php

namespace App\Models;

use App\Models\Scopes\StoreScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

class ProductGroup extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'store_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'group_id');
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public static function generateUniqueSlug(string $name, ?int $storeId = null): string
    {
        $slug = Str::slug($name);
        $originalSlug = $slug;
        $counter = 1;

        $query = self::where('slug', $slug);
        if ($storeId) {
            $query->where('store_id', $storeId);
        }

        while ($query->exists()) {
            $slug = $originalSlug . '-' . $counter;
            $query = self::where('slug', $slug);
            if ($storeId) {
                $query->where('store_id', $storeId);
            }
            $counter++;
        }

        return $slug;
    }

    protected static function booted(): void
    {
        parent::boot();
        
        // Re-enable global scope for multi-store functionality
        static::addGlobalScope(new StoreScope);
        
        static::creating(function (ProductGroup $group) {
            // Auto assign store_id if not set
            if (!$group->store_id && session('selected_store_id')) {
                $group->store_id = session('selected_store_id');
            }
            
            $group->slug = self::generateUniqueSlug($group->name, $group->store_id);
        });

        static::updating(function (ProductGroup $group) {
            $group->slug = self::generateUniqueSlug($group->name, $group->store_id);
        });
    }
}
