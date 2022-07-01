<?php

declare(strict_types=1);

namespace App\Service\NetworkStats;

use App\Service\NetworkStats\NetworkData\Received;
use App\Service\NetworkStats\NetworkData\Transmitted;
use Brick\Math\BigDecimal;
use Brick\Math\BigInteger;

final class NetworkData
{
    public function __construct(
        public readonly string $interfaceName,
        public readonly BigDecimal $time,
        public readonly Received $received,
        public readonly Transmitted $transmitted,
        public readonly bool $isDelta = false
    ) {
    }

    public static function fromInterfaceData(
        string $interfaceName,
        BigDecimal $time,
        array $interfaceData
    ): self {
        $received = new Received(
            BigInteger::of($interfaceData[0] ?? 0),
            BigInteger::of($interfaceData[1] ?? 0),
            BigInteger::of($interfaceData[2] ?? 0),
            BigInteger::of($interfaceData[3] ?? 0),
            BigInteger::of($interfaceData[4] ?? 0),
            BigInteger::of($interfaceData[5] ?? 0),
            BigInteger::of($interfaceData[6] ?? 0),
            BigInteger::of($interfaceData[7] ?? 0)
        );

        $transmitted = new Transmitted(
            BigInteger::of($interfaceData[8] ?? 0),
            BigInteger::of($interfaceData[9] ?? 0),
            BigInteger::of($interfaceData[10] ?? 0),
            BigInteger::of($interfaceData[11] ?? 0),
            BigInteger::of($interfaceData[12] ?? 0),
            BigInteger::of($interfaceData[13] ?? 0),
            BigInteger::of($interfaceData[14] ?? 0),
            BigInteger::of($interfaceData[15] ?? 0)
        );

        return new self(
            $interfaceName,
            $time,
            $received,
            $transmitted
        );
    }
}
