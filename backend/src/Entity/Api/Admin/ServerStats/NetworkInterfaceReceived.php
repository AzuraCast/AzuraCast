<?php

declare(strict_types=1);

namespace App\Entity\Api\Admin\ServerStats;

use App\Radio\Quota;
use App\Service\ServerStats\NetworkData\Received;
use Brick\Math\BigInteger;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Api_Admin_ServerStats_NetworkInterfaceReceived',
    required: ['*'],
    type: 'object'
)]
final readonly class NetworkInterfaceReceived
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
        public string $compressed,
        #[OA\Property]
        public string $multicast
    ) {
    }

    public static function fromReceived(
        Received $received,
        BigInteger $speed
    ): self {
        return new self(
            (string)$speed,
            Quota::getReadableSize($speed),
            (string)$received->packets,
            (string)$received->errs,
            (string)$received->drop,
            (string)$received->fifo,
            (string)$received->frame,
            (string)$received->compressed,
            (string)$received->multicast
        );
    }
}
