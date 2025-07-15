<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Http\Requests\OrderRequest;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Random\RandomException;

class OrderController extends Controller
{
    /**
     * Создание заказа
     *
     * @param OrderRequest $request
     * @return JsonResponse
     * @throws RandomException
     */
    public function store(OrderRequest $request): JsonResponse
    {
        $userId = $request->input('user_id');
        $products = $request->input('products', []);

        $order = Order::create([
            'user_id' => $userId,
            'number' => random_int(1, 100),
            'status' => OrderStatus::New,
            'order_date' => now(),
        ]);

        foreach ($products as $item) {
            $product = Product::findOrFail($item['id']);

            $order->orderItems()->create([
                'product_id' => $product->id,
                'quantity' => $item['quantity'],
                'price_at_order' => $product->price,
            ]);
        }

        return response()->json(['id' => $order->id], 201);
    }

    /**
     * Изменение статуса заказа и списание суммы за товары с баланса покупателя
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function approve(Request $request): JsonResponse
    {
        $orderId = $request->input('order_id');

        $order = Order::with('orderItems', 'user')
            ->where('id', $orderId)
            ->where('status', OrderStatus::New)
            ->first();

        if (!$order) {
            return response()->json(['message' => 'Not found'],404);
        }

        $user = $order->user;

        $total = $order->orderItems->sum(function ($item) {
            return $item->price_at_order * $item->quantity;
        });

        if ($user->amount_of_money < $total) {
            $order->status = OrderStatus::Cancelled;
            $order->save();

            return response()->json(['message' => 'No many'], 400);
        }

        $user->amount_of_money -= $total;
        $user->save();

        $order->status = OrderStatus::Paid;
        $order->save();

        return response()->json(['message' => 'Success']);
    }
}
