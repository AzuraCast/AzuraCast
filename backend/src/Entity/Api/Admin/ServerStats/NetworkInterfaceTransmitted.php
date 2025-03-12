<?php

declare(strict_types=1);

namespace App\Entity\Api\Admin\ServerStats;

use App\Radio\Quota;
use App\Service\ServerStats\NetworkData\Transmitted;
use Brick\Math\BigInteger;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Api_Admin_ServerStats_NetworkInterfaceTransmitted',
    required: ['*'],
    type: 'object'
)]
final class NetworkInterfaceTransmitted
{
    public function __construct(
        #[OA\Property]
        public string $speed_bytes,
        #[OA\Property]
        public string $speed_readable,
        #[OA\Property]
        public string $packets,
        #[OA\Property]
        public string $errs,
        #[OA\Property]
        public string $drop,
        #[OA\Property]
        public string $fifo,
        #[OA\Property]
        public string $frame,
        #[OA\Property]
        public string $carrier,
        #[OA\Property]
        public string $compressed
    ) {
    }

    public static function fromTransmitted(
        Transmitted $transmitted,
        BigInteger $speed
    ): self {
        return new self(
            (string)$speed,
            Quota::getReadableSize($speed),
            (string)$transmitted->packets,
            (string)$transmitted->errs,
            (string)$transmitted->drop,
            (string)$transmitted->fifo,
            (string)$transmitted->colls,
            (string)$transmitted->compressed,
            (string)$transmitted->carrier
        );
    }
}
