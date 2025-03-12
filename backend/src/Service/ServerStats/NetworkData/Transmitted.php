<?php

declare(strict_types=1);

namespace App\Service\ServerStats\NetworkData;

use Brick\Math\BigInteger;

final readonly class Transmitted
{
    public function __construct(
        // The total number of bytes of data transmitted by the interface.
        public BigInteger $bytes,
        // The total number of packets of data transmitted by the interface.
        public BigInteger $packets,
        // The total number of transmit errors detected by the device driver.
        public BigInteger $errs,
        // The total number of packets dropped by the device driver.
        public BigInteger $drop,
        // The number of FIFO buffer errors.
        public BigInteger $fifo,
        // The number of collisions detected on the interface.
        public BigInteger $colls,
        // The number of carrier losses detected by the device driver.
        public BigInteger $carrier,
        // The number of compressed packets transmitted by the device driver.
        public BigInteger $compressed
    ) {
    }
}
