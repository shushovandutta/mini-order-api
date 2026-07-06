<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\v1\StoreProductRequest;
use App\Http\Requests\Api\v1\UpdateProductRequest;
use App\Http\Resources\Api\v1\ProductResource;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $products = Product::query()
            ->search($request->query('search'))
            ->minPrice($request->query('min_price'))
            ->maxPrice($request->query('max_price'))
            ->latest()
            ->paginate(10);

        return ProductResource::collection($products);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProductRequest $request)
    {
        $product = Product::create($request->validated());

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
        //Authomatically will found the product and if not found it will 404 error
        return response()->json([
            'status' => 'success',
            'data' => new ProductResource($product)
        ], Response::HTTP_OK);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProductRequest $request, Product $product)
    {
        $product->update($request->validated());

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
        $product->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Product deleted successfully'
        ], Response::HTTP_OK);
    }
}
