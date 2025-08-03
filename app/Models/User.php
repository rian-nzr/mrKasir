<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'store_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole('super_admin');
    }

    public function canAccessAllStores(): bool
    {
        return $this->isSuperAdmin();
    }

    public function hasStoreRestriction(): bool
    {
        return !$this->isSuperAdmin() && $this->store_id !== null;
    }

    public function getAccessibleStoreIds(): array
    {
        if ($this->isSuperAdmin()) {
            // Super admin can access all stores
            return Store::pluck('id')->toArray();
        }
        
        if ($this->store_id) {
            // User has specific store restriction
            return [$this->store_id];
        }
        
        // Default case
        return [];
    }
}
