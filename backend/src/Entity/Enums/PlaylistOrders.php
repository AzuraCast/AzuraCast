<?php

declare(strict_types=1);

namespace App\Entity\Enums;

enum PlaylistOrders: string
{
    case Random = 'random';
    case Shuffle = 'shuffle';
    case Sequential = 'sequential';
}
