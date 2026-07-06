<?php

namespace App\Models;

use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'total_price', 'status'];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            // 2. Custing as per Laravel 12 standards
            'status' => OrderStatus::class,
        ];
    }

    // One order can have mutiple items
    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    // An order belongs to a User Relationship
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
