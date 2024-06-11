<?php

declare(strict_types=1);

namespace App\Service\NetworkStats\NetworkData;

use Brick\Math\BigInteger;

final class Received
{
    /**
     * The total number of bytes of data received by the interface.
     */
    public readonly BigInteger $bytes;

    /**
     * The total number of packets of data received by the interface.
     */
    public readonly BigInteger $packets;

    /**
     * The total number of receive errors detected by the device driver.
     */
    public readonly BigInteger $errs;

    /**
     * The total number of packets dropped by the device driver.
     */
    public readonly BigInteger $drop;

    /**
     * The number of FIFO buffer errors.
     */
    public readonly BigInteger $fifo;

    /**
     * The number of packet framing errors.
     */
    public readonly BigInteger $frame;

    /**
     * The number of compressed packets received by the device driver.
     */
    public readonly BigInteger $compressed;

    /**
     * The number of multicast frames received by the device driver.
     */
    public readonly BigInteger $multicast;

    public function __construct(
        BigInteger $bytes,
        BigInteger $packets,
        BigInteger $errs,
        BigInteger $drop,
        BigInteger $fifo,
        BigInteger $frame,
        BigInteger $compressed,
        BigInteger $multicast
    ) {
        $this->bytes = $bytes;
        $this->packets = $packets;
        $this->errs = $errs;
        $this->drop = $drop;
        $this->fifo = $fifo;
        $this->frame = $frame;
        $this->compressed = $compressed;
        $this->multicast = $multicast;
    }
}
