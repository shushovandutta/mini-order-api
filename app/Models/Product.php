<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'description',
        'price',
        'stock'
    ];

    /**
     * Scope: Product Name/Description Search
     */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        return $query->when($term, function ($query, $term) {
            $query->where(function ($q) use ($term) {
                // ক্যারেক্টার সেফটির জন্য less than (<) বা greater than (>) সাইন টেক্সটে ব্যবহার না করে SQL query-তে রাখা হলো
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('description', 'like', "%{$term}%");
            });
        });
    }

    /**
     * Scope: Filter by Minimum Price
     */
    public function scopeMinPrice(Builder $query, ?float $price): Builder
    {
        return $query->when($price, function ($query, $price) {
            $query->where('price', '>=', $price);
        });
    }

    /**
     * Scope: Filter by Maximum Price
     */
    public function scopeMaxPrice(Builder $query, ?float $price): Builder
    {
        return $query->when($price, function ($query, $price) {
            $query->where('price', '<=', $price);
        });
    }
}