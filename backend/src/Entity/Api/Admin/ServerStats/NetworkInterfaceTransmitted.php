<?php

declare(strict_types=1);

namespace App\Entity\Api\Admin\ServerStats;

use App\Radio\Quota;
use App\Service\ServerStats\NetworkData\Transmitted;
use Brick\Math\BigInteger;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Api_Admin_ServerStats_NetworkInterfaceTransmitted',
    type: 'object'
)]
final class NetworkInterfaceTransmitted
{
    #[OA\Property]
    public string $speed_bytes;

    #[OA\Property]
    public string $speed_readable;

    #[OA\Property]
    public string $packets;

    #[OA\Property]
    public string $errs;

    #[OA\Property]
    public string $drop;

    #[OA\Property]
    public string $fifo;

    #[OA\Property]
    public string $frame;

    #[OA\Property]
    public string $carrier;

    #[OA\Property]
    public string $compressed;

    public static function fromTransmitted(
        Transmitted $transmitted,
        BigInteger $speed
    ): self {
        $return = new self();

        $return->speed_bytes = (string)$speed;
        $return->speed_readable = Quota::getReadableSize($speed);

        $return->packets = (string)$transmitted->packets;
        $return->errs = (string)$transmitted->errs;
        $return->drop = (string)$transmitted->drop;
        $return->fifo = (string)$transmitted->fifo;
        $return->frame = (string)$transmitted->colls;
        $return->compressed = (string)$transmitted->compressed;
        $return->carrier = (string)$transmitted->carrier;

        return $return;
    }
}
