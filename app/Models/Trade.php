<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Trade extends Model
{
    protected $fillable = [
        'symbol',
        'price',
        'amount',
        'buy_order_id',
        'sell_order_id',
        'buyer_id',
        'seller_id',
        'matched_usd',
        'fee_usd',
    ];

    protected $casts = [
        'price' => 'decimal:8',
        'amount' => 'decimal:8',
        'matched_usd' => 'decimal:8',
        'fee_usd' => 'decimal:8',
    ];

    public function buyer(): BelongsTo { return $this->belongsTo(User::class, 'buyer_id'); }
    public function seller(): BelongsTo { return $this->belongsTo(User::class, 'seller_id'); }
}
