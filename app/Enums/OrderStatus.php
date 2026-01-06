<?php

namespace App\Enums;

enum OrderStatus: string
{
    case OPEN = 'OPEN';
    case MATCHED = 'MATCHED';
    case CANCELLED = 'CANCELLED';

    public static function values(): array
    {
        return array_map(fn($c) => $c->value, self::cases());
    }
}
