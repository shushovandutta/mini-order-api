<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->words(3, true), // 3 character fake name
            'description' => $this->faker->paragraph(2), // 2 lined fake description
            'price' => $this->faker->randomFloat(2, 10, 1000), // 10 to 1000 valued fake product price in decimal number
            'stock' => $this->faker->numberBetween(10, 100), // 10 10 100 piece stock
        ];
    }
}
