<?php

namespace App\Events;

use App\Models\Trade;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderMatched implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Trade $trade
    ) {}

    public function broadcastAs(): string
    {
        return 'OrderMatched';
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.' . $this->trade->buyer_id),
            new PrivateChannel('user.' . $this->trade->seller_id),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'trade' => [
                'id' => $this->trade->id,
                'symbol' => $this->trade->symbol,
                'price' => (string) $this->trade->price,
                'amount' => (string) $this->trade->amount,
                'matched_usd' => (string) $this->trade->matched_usd,
                'fee_usd' => (string) $this->trade->fee_usd,
                'buy_order_id' => $this->trade->buy_order_id,
                'sell_order_id' => $this->trade->sell_order_id,
                'buyer_id' => $this->trade->buyer_id,
                'seller_id' => $this->trade->seller_id,
                'created_at' => $this->trade->created_at?->toISOString(),
            ],
        ];
    }
}
