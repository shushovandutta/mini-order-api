<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\v1\StoreOrderRequest;
use App\Services\OrderService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class OrderController extends Controller
{
    protected $orderService;

    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService;
    }

    /**
     * Create Order API
     */
    public function store(StoreOrderRequest $request)
    {
        // Checking whether user logedin or not
        if (!$request->user()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized. Please login again.'
            ], Response::HTTP_UNAUTHORIZED); //401 error
        }

        try {
            $order = $this->orderService->placeOrder(
                $request->user()->id,
                $request->items
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Order placed successfully',
                'data' => $order->load('items.product')
            ], Response::HTTP_CREATED); //201 success
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], Response::HTTP_BAD_REQUEST); //400 error
        }
    }

    /**
     * Get User Orders List
     */
    public function index(Request $request)
    {
        $orders = $request->user()->orders()->with('items.product')->latest()->paginate(10);

        return response()->json([
            'status' => 'success',
            'data' => $orders
        ], Response::HTTP_OK); //200 Success
    }

    /**
     * Get Single Order Details
     */
    public function show(Request $request, $id)
    {
        // User can only see his/her order, that's why we are fetching data using user scope and it is secure
        $order = $request->user()->orders()->with('items.product')->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $order
        ], Response::HTTP_OK); //200 Success
    }
}
