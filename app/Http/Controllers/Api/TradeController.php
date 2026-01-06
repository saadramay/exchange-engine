<?php

namespace App\Http\Controllers\Api;

use App\Enums\Symbol;
use App\Http\Controllers\Controller;
use App\Models\Trade;
use Illuminate\Http\Request;

class TradeController extends Controller
{
    public function index(Request $request)
    {
        $symbol = $request->query('symbol', Symbol::BTC->value);
        $limit = (int) $request->query('limit', 50);

        if (!in_array($symbol, Symbol::values(), true)) {
            return response()->json(['message' => 'Invalid symbol'], 422);
        }

        $limit = max(1, min($limit, 200));

        $trades = Trade::query()
            ->where('symbol', $symbol)
            ->latest()
            ->limit($limit)
            ->get([
                'id',
                'symbol',
                'price',
                'amount',
                'matched_usd',
                'fee_usd',
                'buyer_id',
                'seller_id',
                'created_at',
            ]);

        return response()->json([
            'symbol' => $symbol,
            'trades' => $trades,
        ]);
    }

    public function myTrades(Request $request)
    {
        $user = $request->user();

        $symbol = $request->query('symbol'); // optional
        $limit = (int) $request->query('limit', 50);

        if ($symbol !== null && !in_array($symbol, Symbol::values(), true)) {
            return response()->json(['message' => 'Invalid symbol'], 422);
        }

        $limit = max(1, min($limit, 200));

        $query = Trade::query()
            ->where(function ($q) use ($user) {
                $q->where('buyer_id', $user->id)
                  ->orWhere('seller_id', $user->id);
            });

        if ($symbol !== null) {
            $query->where('symbol', $symbol);
        }

        $trades = $query
            ->latest()
            ->limit($limit)
            ->get([
                'id',
                'symbol',
                'price',
                'amount',
                'matched_usd',
                'fee_usd',
                'buyer_id',
                'seller_id',
                'created_at',
            ]);

        return response()->json([
            'trades' => $trades,
        ]);
    }
}
