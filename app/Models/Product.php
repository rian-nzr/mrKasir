<?php

namespace App\Models;

use App\Models\Scopes\StoreScope;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'category_id',
        'group_id',
        'store_id',
        'stock',
        'price',
        'cost_price',
        'is_active',
        'image',
        'barcode',
        'description',
    ];

    protected $appends = ['image_url'];


    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(ProductGroup::class, 'group_id');
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public static function generateUniqueSlug(string $name): string
    {
        $slug = Str::slug($name);
        $originalSlug = $slug;
        $counter = 1;

        while (self::where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    public function getImageUrlAttribute()
    {
        return $this->image ? url('storage/'. $this->image) : null;
    }

    public function getProfitAttribute()
    {
        if ($this->cost_price && $this->price) {
            return $this->price - $this->cost_price;
        }
        return 0;
    }

    public function getProfitPercentageAttribute()
    {
        if ($this->cost_price && $this->price && $this->cost_price > 0) {
            return round((($this->price - $this->cost_price) / $this->cost_price) * 100, 2);
        }
        return 0;
    }

    public function scopeSearch($query, $value)
    {
        $query->where("name", "like", "%{$value}%");
    }

    public function orderProducts(): HasMany
    {
        return $this->hasMany(OrderProduct::class);
    }

    protected static function booted(): void
    {
        parent::boot();
        
        // Re-enable global scope for multi-store functionality
        static::addGlobalScope(new StoreScope);
        
        static::creating(function (Product $product) {
            $product->slug = self::generateUniqueSlug($product->name);
            
            // Auto assign store_id if not set
            if (!$product->store_id && session('selected_store_id')) {
                $product->store_id = session('selected_store_id');
            }
        });

        static::updating(function (Product $product) {
            $product->slug = self::generateUniqueSlug($product->name);
        });
    }
}
