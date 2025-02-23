<?php

declare(strict_types=1);

namespace App\Entity\Api\Admin\ServerStats;

use App\Radio\Quota;
use App\Service\ServerStats\NetworkData\Received;
use Brick\Math\BigInteger;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Api_Admin_ServerStats_NetworkInterfaceReceived',
    type: 'object'
)]
final class NetworkInterfaceReceived
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
    public string $compressed;

    #[OA\Property]
    public string $multicast;

    public static function fromReceived(
        Received $received,
        BigInteger $speed
    ): self {
        $return = new self();

        $return->speed_bytes = (string)$speed;
        $return->speed_readable = Quota::getReadableSize($speed);

        $return->packets = (string)$received->packets;
        $return->errs = (string)$received->errs;
        $return->drop = (string)$received->drop;
        $return->fifo = (string)$received->fifo;
        $return->frame = (string)$received->frame;
        $return->compressed = (string)$received->compressed;
        $return->multicast = (string)$received->multicast;
        return $return;
    }
}
