<?php

namespace Database\Seeders;

use App\Enums\OrderSide;
use App\Enums\OrderStatus;
use App\Models\Asset;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DemoExchangeSeeder extends Seeder
{
    private const SCALE = 8;
    private const FEE_RATE = 0.015;

    public function run(): void
    {
        DB::transaction(function () {
            $buyer = User::updateOrCreate(
                ['email' => 'buyer@test.com'],
                [
                    'name' => 'Buyer',
                    'password' => Hash::make('password'),
                    'balance' => 100000,
                ]
            );

            $seller = User::updateOrCreate(
                ['email' => 'seller@test.com'],
                [
                    'name' => 'Seller',
                    'password' => Hash::make('password'),
                    'balance' => 0,
                ]
            );

            $buyerBtc = Asset::updateOrCreate(
                ['user_id' => $buyer->id, 'symbol' => 'BTC'],
                ['amount' => 0, 'locked_amount' => 0]
            );

            $sellerBtc = Asset::updateOrCreate(
                ['user_id' => $seller->id, 'symbol' => 'BTC'],
                ['amount' => 10, 'locked_amount' => 0]
            );

            Order::query()
                ->whereIn('user_id', [$buyer->id, $seller->id])
                ->where('symbol', 'BTC')
                ->where('status', OrderStatus::OPEN->value)
                ->delete();

            $sellAmount = 0.10;
            $sellPrice  = 100000;

            $sellerBtc->refresh();
            $sellerBtc->amount = round(((float) $sellerBtc->amount - $sellAmount), self::SCALE);
            $sellerBtc->locked_amount = round(((float) $sellerBtc->locked_amount + $sellAmount), self::SCALE);
            $sellerBtc->save();

            Order::create([
                'user_id' => $seller->id,
                'symbol' => 'BTC',
                'side' => OrderSide::SELL->value,
                'price' => $sellPrice,
                'amount' => $sellAmount,
                'status' => OrderStatus::OPEN->value,
                'locked_usd' => 0,
            ]);

            $buyAmount = 0.10;
            $buyPrice  = 90000;

            $buyer->refresh();
            $usdValue = $buyPrice * $buyAmount;
            $feeBuffer = $usdValue * self::FEE_RATE;
            $totalLock = round($usdValue + $feeBuffer, self::SCALE);
            $buyer->balance = round(((float) $buyer->balance - $totalLock), self::SCALE);
            $buyer->save();

            Order::create([
                'user_id' => $buyer->id,
                'symbol' => 'BTC',
                'side' => OrderSide::BUY->value,
                'price' => $buyPrice,
                'amount' => $buyAmount,
                'status' => OrderStatus::OPEN->value,
                'locked_usd' => $totalLock,
            ]);

            Asset::updateOrCreate(
                ['user_id' => $buyer->id, 'symbol' => 'ETH'],
                ['amount' => 0, 'locked_amount' => 0]
            );

            Asset::updateOrCreate(
                ['user_id' => $seller->id, 'symbol' => 'ETH'],
                ['amount' => 5, 'locked_amount' => 0]
            );
        });
    }
}
