<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Exception;

class OrderService
{
    public function placeOrder(int $userId, array $items): Order
    {
        // DB Transaction starts here. If any operation failed then the entire transaction will be rolled back
        return DB::transaction(function () use ($userId, $items) {
            $totalPrice = 0;
            $calculatedItems = [];

            foreach ($items as $item) {
                //  Using lockForUpdate() to lock the row so that any other transaction can not change the current stock at the same time
                // Pessimistic Locking
                $product = Product::lockForUpdate()->find($item['product_id']);

                // 1. Check the Stock
                if ($product->stock < $item['quantity']) {
                    throw new Exception("Insufficient stock for product: {$product->name}. Available stock: {$product->stock}");
                }

                // 2. Decrease the Stock Qty
                $product->decrement('stock', $item['quantity']);

                // 3. Calculate Price
                $itemTotalPrice = $product->price * $item['quantity'];
                $totalPrice += $itemTotalPrice;

                // Creating the array for inserting data when we will get the product
                $calculatedItems[] = [
                    'product_id' => $product->id,
                    'quantity' => $item['quantity'],
                    'price' => $product->price
                ];
            }

            // 4. Create Order
            $order = Order::create([
                'user_id' => $userId,
                'total_price' => $totalPrice,
                'status' => OrderStatus::COMPLETED
            ]);

            // 5. Save order items to the Database
            foreach ($calculatedItems as $calculatedItem) {
                $order->items()->create($calculatedItem);
            }

            return $order;
        });
    }
}
