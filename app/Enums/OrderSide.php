<?php

namespace App\Enums;

enum OrderSide: string
{
    case BUY = 'BUY';
    case SELL = 'SELL';

    public static function values(): array
    {
        return array_map(fn($c) => $c->value, self::cases());
    }
}
