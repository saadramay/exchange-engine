<?php

namespace App\Http\Controllers\Api;

use App\Enums\OrderSide;
use App\Enums\OrderStatus;
use App\Enums\Symbol;
use App\Services\MatchingService;
use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    public function __construct(private readonly MatchingService $matching) {}

    public function index(Request $request)
    {
        $symbol = $request->query('symbol', Symbol::BTC->value);

        if (!in_array($symbol, Symbol::values(), true)) {
            return response()->json(['message' => 'Invalid symbol'], 422);
        }

        $orders = Order::query()
            ->where('symbol', $symbol)
            ->where('status', OrderStatus::OPEN->value)
            ->orderBy('created_at') // FIFO base; UI can group by side/price
            ->get(['id', 'user_id', 'symbol', 'side', 'price', 'amount', 'status', 'created_at']);

        return response()->json([
            'symbol' => $symbol,
            'orders' => $orders,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'symbol' => ['required', Rule::in(Symbol::values())],
            'side'   => ['required', Rule::in(OrderSide::values())],
            'price'  => ['required', 'numeric', 'gt:0'],
            'amount' => ['required', 'numeric', 'gt:0'],
        ]);

        $user = $request->user();

        // Use strings for BCMath
        $price  = (string) $data['price'];
        $amount = (string) $data['amount'];

        $created = DB::transaction(function () use ($user, $data, $price, $amount) {
            $user = $user->newQuery()->lockForUpdate()->findOrFail($user->id);

            $lockedUsd = '0';

            if ($data['side'] === OrderSide::BUY->value) {
                $usdValue   = bcmul($price, $amount, 8);
                $feeBuffer  = bcmul($usdValue, '0.015', 8);
                $totalLock  = bcadd($usdValue, $feeBuffer, 8);

                if (bccomp((string) $user->balance, $totalLock, 8) < 0) {
                    abort(422, 'Insufficient USD balance');
                }

                $user->balance = bcsub((string) $user->balance, $totalLock, 8);
                $user->save();

                $lockedUsd = $totalLock;
            }

            if ($data['side'] === OrderSide::SELL->value) {
                $asset = Asset::query()
                    ->where('user_id', $user->id)
                    ->where('symbol', $data['symbol'])
                    ->lockForUpdate()
                    ->first();

                if (!$asset) {
                    abort(422, 'No asset balance');
                }

                if (bccomp((string) $asset->amount, $amount, 8) < 0) {
                    abort(422, 'Insufficient asset amount');
                }

                $asset->amount = bcsub((string) $asset->amount, $amount, 8);
                $asset->locked_amount = bcadd((string) $asset->locked_amount, $amount, 8);
                $asset->save();
            }

            return Order::create([
                'user_id' => $user->id,
                'symbol' => $data['symbol'],
                'side' => $data['side'],
                'price' => $price,
                'amount' => $amount,
                'status' => OrderStatus::OPEN->value,
                'locked_usd' => $lockedUsd,
            ]);
        });

        $trade = $this->matching->matchOrder($created->id);

        return response()->json([
            'order' => $created,
            'matched' => $trade ? true : false,
            'trade_id' => $trade?->id,
        ], 201);
    }

    public function match(Request $request, Order $order)
    {
        if ($order->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $trade = $this->matching->matchOrder($order->id);

        return response()->json([
            'matched' => $trade ? true : false,
            'trade' => $trade,
        ]);
    }


    public function cancel(Request $request, Order $order)
    {
        $user = $request->user();

        if ($order->user_id !== $user->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $cancelled = DB::transaction(function () use ($user, $order) {
            $order = Order::query()->lockForUpdate()->findOrFail($order->id);

            if ($order->status !== OrderStatus::OPEN->value) {
                abort(422, 'Only OPEN orders can be cancelled');
            }

            $user = $user->newQuery()->lockForUpdate()->findOrFail($user->id);

            if ($order->side === OrderSide::BUY->value) {
                $user->balance = bcadd((string) $user->balance, (string) $order->locked_usd, 8);
                $user->save();

                $order->locked_usd = '0';
            }

            if ($order->side === OrderSide::SELL->value) {
                $asset = Asset::query()
                    ->where('user_id', $user->id)
                    ->where('symbol', $order->symbol)
                    ->lockForUpdate()
                    ->firstOrFail();

                $asset->locked_amount = bcsub((string) $asset->locked_amount, (string) $order->amount, 8);
                $asset->amount = bcadd((string) $asset->amount, (string) $order->amount, 8);
                $asset->save();
            }

            $order->status = OrderStatus::CANCELLED->value;
            $order->cancelled_at = now();
            $order->save();

            return $order;
        });

        return response()->json(['order' => $cancelled]);
    }

    public function myOrders(Request $request)
    {
        $user = $request->user();

        $orders = Order::query()
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->get([
                'id',
                'symbol',
                'side',
                'price',
                'amount',
                'status',
                'locked_usd',
                'matched_at',
                'cancelled_at',
                'created_at'
            ]);

        return response()->json([
            'orders' => $orders,
        ]);
    }
}
