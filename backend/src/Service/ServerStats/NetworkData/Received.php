<?php

declare(strict_types=1);

namespace App\Service\ServerStats\NetworkData;

use Brick\Math\BigInteger;

final readonly class Received
{
    public function __construct(
        // The total number of bytes of data received by the interface.
        public BigInteger $bytes,
        // The total number of packets of data received by the interface.
        public BigInteger $packets,
        // The total number of receive errors detected by the device driver.
        public BigInteger $errs,
        // The total number of packets dropped by the device driver.
        public BigInteger $drop,
        // The number of FIFO buffer errors.
        public BigInteger $fifo,
        // The number of packet framing errors.
        public BigInteger $frame,
        // The number of compressed packets received by the device driver.
        public BigInteger $compressed,
        // The number of multicast frames received by the device driver.
        public BigInteger $multicast
    ) {
    }
}
