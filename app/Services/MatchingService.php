<?php

namespace App\Services;

use App\Enums\OrderSide;
use App\Enums\OrderStatus;
use App\Events\OrderMatched;
use App\Models\Asset;
use App\Models\Order;
use App\Models\Trade;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class MatchingService
{
    private const SCALE = 8;
    private const FEE_RATE = '0.015'; // 1.5% buyer pays in USD

    public function matchOrder(int $orderId): ?Trade
    {
        $trade = DB::transaction(function () use ($orderId) {

            /** @var Order $order */
            $order = Order::query()->lockForUpdate()->findOrFail($orderId);

            if ($order->status !== OrderStatus::OPEN->value) {
                return null;
            }

            $counter = $this->findCounterOrderForUpdate($order);
            if (!$counter) {
                return null;
            }

            // Re-lock both orders in a consistent order (reduce deadlocks)
            $ids = [$order->id, $counter->id];
            sort($ids);

            $locked = Order::query()
                ->whereIn('id', $ids)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $order = $locked[$order->id];
            $counter = $locked[$counter->id];

            if (
                $order->status !== OrderStatus::OPEN->value ||
                $counter->status !== OrderStatus::OPEN->value
            ) {
                return null;
            }

            // Full-match only (exact amount match)
            if (bccomp((string) $counter->amount, (string) $order->amount, self::SCALE) !== 0) {
                return null;
            }

            $buyOrder  = $order->side === OrderSide::BUY->value ? $order : $counter;
            $sellOrder = $order->side === OrderSide::SELL->value ? $order : $counter;

            /** @var User $buyer */
            $buyer  = User::query()->lockForUpdate()->findOrFail($buyOrder->user_id);
            /** @var User $seller */
            $seller = User::query()->lockForUpdate()->findOrFail($sellOrder->user_id);

            // Lock assets
            $buyerAsset = Asset::query()
                ->where('user_id', $buyer->id)
                ->where('symbol', $buyOrder->symbol)
                ->lockForUpdate()
                ->first();

            if (!$buyerAsset) {
                $buyerAsset = Asset::create([
                    'user_id' => $buyer->id,
                    'symbol' => $buyOrder->symbol,
                    'amount' => '0',
                    'locked_amount' => '0',
                ]);
                $buyerAsset = Asset::query()->whereKey($buyerAsset->id)->lockForUpdate()->firstOrFail();
            }

            $sellerAsset = Asset::query()
                ->where('user_id', $seller->id)
                ->where('symbol', $sellOrder->symbol)
                ->lockForUpdate()
                ->firstOrFail();

            // Executed price = maker price (existing counter order price)
            $executedPrice = (string) $counter->price;
            $amount = (string) $order->amount;

            $matchedUsd = bcmul($executedPrice, $amount, self::SCALE);
            $feeUsd = bcmul($matchedUsd, self::FEE_RATE, self::SCALE);

            $buyerLockedUsd = (string) $buyOrder->locked_usd;
            $buyerSpend = bcadd($matchedUsd, $feeUsd, self::SCALE);

            if (bccomp($buyerLockedUsd, $buyerSpend, self::SCALE) < 0) {
                abort(500, 'Buyer locked USD is insufficient for settlement');
            }

            if (bccomp((string) $sellerAsset->locked_amount, $amount, self::SCALE) < 0) {
                abort(500, 'Seller locked asset is insufficient for settlement');
            }

            // 1) Move asset from seller locked -> buyer free
            $sellerAsset->locked_amount = bcsub((string) $sellerAsset->locked_amount, $amount, self::SCALE);
            $sellerAsset->save();

            $buyerAsset->amount = bcadd((string) $buyerAsset->amount, $amount, self::SCALE);
            $buyerAsset->save();

            // 2) Pay seller in USD
            $seller->balance = bcadd((string) $seller->balance, $matchedUsd, self::SCALE);
            $seller->save();

            // 3) Buyer spends locked USD, refund remainder
            $refund = bcsub($buyerLockedUsd, $buyerSpend, self::SCALE);

            $buyer->balance = bcadd((string) $buyer->balance, $refund, self::SCALE);
            $buyer->save();

            $buyOrder->locked_usd = '0';
            $buyOrder->status = OrderStatus::MATCHED->value;
            $buyOrder->matched_at = now();
            $buyOrder->save();

            $sellOrder->status = OrderStatus::MATCHED->value;
            $sellOrder->matched_at = now();
            $sellOrder->save();

            return Trade::create([
                'symbol' => $buyOrder->symbol,
                'price' => $executedPrice,
                'amount' => $amount,
                'buy_order_id' => $buyOrder->id,
                'sell_order_id' => $sellOrder->id,
                'buyer_id' => $buyer->id,
                'seller_id' => $seller->id,
                'matched_usd' => $matchedUsd,
                'fee_usd' => $feeUsd,
            ]);
        });

        if ($trade) {
            event(new OrderMatched($trade));
        }

        return $trade;
    }

    private function findCounterOrderForUpdate(Order $order): ?Order
    {
        $symbol = $order->symbol;

        if ($order->side === OrderSide::BUY->value) {
            return Order::query()
                ->where('symbol', $symbol)
                ->where('status', OrderStatus::OPEN->value)
                ->where('side', OrderSide::SELL->value)
                ->where('user_id', '!=', $order->user_id) // prevent self-match
                ->where('price', '<=', $order->price)
                ->where('amount', $order->amount)
                ->orderBy('price', 'asc')
                ->orderBy('created_at', 'asc')
                ->lockForUpdate()
                ->first();
        }

        return Order::query()
            ->where('symbol', $symbol)
            ->where('status', OrderStatus::OPEN->value)
            ->where('side', OrderSide::BUY->value)
            ->where('user_id', '!=', $order->user_id) // prevent self-match
            ->where('price', '>=', $order->price)
            ->where('amount', $order->amount)
            ->orderBy('price', 'desc')
            ->orderBy('created_at', 'asc')
            ->lockForUpdate()
            ->first();
    }
}
