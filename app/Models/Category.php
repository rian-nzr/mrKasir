<?php

namespace App\Models;

use App\Models\Scopes\StoreScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'store_id',
    ];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class)
            ->with('orderProducts');
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

    protected static function booted(): void
    {
        parent::boot();
        
        // Re-enable global scope for multi-store functionality
        static::addGlobalScope(new StoreScope);
        
        static::creating(function (Category $category) {
            $category->slug = self::generateUniqueSlug($category->name);
            
            // Auto assign store_id if not set
            if (!$category->store_id && session('selected_store_id')) {
                $category->store_id = session('selected_store_id');
            }
        });

        static::updating(function (Category $category) {
            $category->slug = self::generateUniqueSlug($category->name);
        });
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }
}
