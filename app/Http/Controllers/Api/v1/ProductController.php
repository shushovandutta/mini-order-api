<?php

namespace App\Http\Controllers\Api\v1;

use Illuminate\Support\Facades\Redis;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\v1\StoreProductRequest;
use App\Http\Requests\Api\v1\UpdateProductRequest;
use App\Http\Resources\Api\v1\ProductResource;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $page = $request->query('page', 1);
        $search = $request->query('search', '');
        $minPrice = $request->query('min_price', '');
        $maxPrice = $request->query('max_price', '');

        // Creating a unique cache key based on search filter and page 
        $cacheKey = "products_page_{$page}_{$search}_{$minPrice}_{$maxPrice}";

        // Cache::remember method will check cache, if cache not found it will fetch data from database and store it to the radis cache for 3600 seconds (1 hour)
        $products = Cache::remember($cacheKey, 3600, function () use ($request) {
            return Product::query()
                ->search($request->query('search'))
                ->minPrice($request->query('min_price'))
                ->maxPrice($request->query('max_price'))
                ->latest()
                ->paginate(10);
        });

        return ProductResource::collection($products);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProductRequest $request)
    {
        $product = Product::create($request->validated());

        //Cache Delete
        $this->clearProductCache($product->id);

        return response()->json([
            'status' => 'success',
            'message' => 'Product created successfully',
            'data' => new ProductResource($product)
        ], Response::HTTP_CREATED); //201 response code
    }

    /**
     * Display the specified resource.
     */
    public function show(Product $product)
    {
        $cacheKey = "product_single_{$product->id}";

        $cachedProduct = Cache::remember($cacheKey, 3600, function () use ($product) {
            return $product;
        });
        //Authomatically will found the product and if not found it will 404 error
        return response()->json([
            'status' => 'success',
            'data' => new ProductResource($cachedProduct)
        ], Response::HTTP_OK);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProductRequest $request, Product $product)
    {
        $product->update($request->validated());

        //Cache Delete
        $this->clearProductCache($product->id);

        return response()->json([
            'status' => 'success',
            'message' => 'Product updated successfully',
            'data' => new ProductResource($product)
        ], Response::HTTP_OK);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product)
    {
        $productId = $product->id;
        $product->delete();

        // Cache Delete
        $this->clearProductCache($productId);

        return response()->json([
            'status' => 'success',
            'message' => 'Product deleted successfully'
        ], Response::HTTP_OK);
    }

    /**
     * Helper Method to flush specific product caches
     */
    private function clearProductCache($productId = null)
    {
        // Laravel Redis facade connection
        $redis = \Illuminate\Support\Facades\Redis::connection();

        // Only find out the keys of the product pages
        $keys = $redis->keys('*products_page_*');

        if (!empty($keys)) {
            foreach ($keys as $key) {
                $redis->del($key);
            }
        }

        // Clear cache of a single product (Using standard laravel cache facade)
        if ($productId) {
            \Illuminate\Support\Facades\Cache::forget("product_single_{$productId}");
        }
    }
}
