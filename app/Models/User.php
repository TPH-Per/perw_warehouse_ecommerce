<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens, SoftDeletes;

    // Table name (if different from plural convention)
    protected $table = 'Users';

    // Columns that can be mass assigned
    protected $fillable = [
        'role_id',
        'full_name',
        'email',
        'password_hash',
        'phone_number',
        'avatar_url',
        'status',
    ];

    // Columns hidden when serializing (e.g.: password)
    protected $hidden = [
        'password_hash',
        'remember_token',
    ];

    // Boolean column casts
    protected $casts = [
        'is_deleted' => 'boolean',
        'is_default' => 'boolean',
        'is_approved' => 'boolean',
        'is_active' => 'boolean',
        'email_verified_at' => 'datetime',
    ];

    // Soft delete timestamp columns
    protected $dates = ['deleted_at'];

    // Relationship with Role
    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    // Relationship with Addresses
    public function addresses()
    {
        return $this->hasMany(Address::class, 'user_id');
    }

    // Relationship with Cart
    public function cart()
    {
        return $this->hasOne(Cart::class, 'user_id');
    }

    // Relationship with PurchaseOrders
    public function purchaseOrders()
    {
        return $this->hasMany(PurchaseOrder::class, 'user_id');
    }

    // Helper method to check Admin role
    public function isAdmin(): bool
    {
        return $this->role_id === 1; // Assuming role_id 1 is Admin
    }

    // Override password method for Laravel Auth to work with password_hash column
    public function getAuthPassword()
    {
        return $this->password_hash;
    }

    // Override to specify the password field name for authentication
    public function getAuthPasswordName()
    {
        return 'password_hash';
    }
}