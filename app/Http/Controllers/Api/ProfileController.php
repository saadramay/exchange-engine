<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();

        $assets = Asset::query()
            ->where('user_id', $user->id)
            ->orderBy('symbol')
            ->get(['symbol', 'amount', 'locked_amount']);

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
            ],
            'usd' => [
                'balance' => (string) $user->balance,
            ],
            'assets' => $assets,
        ]);
    }
}
