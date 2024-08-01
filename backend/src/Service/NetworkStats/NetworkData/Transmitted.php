<?php

declare(strict_types=1);

namespace App\Service\NetworkStats\NetworkData;

use Brick\Math\BigInteger;

final class Transmitted
{
    /**
     * The total number of bytes of data transmitted by the interface.
     */
    public readonly BigInteger $bytes;

    /**
     * The total number of packets of data transmitted by the interface.
     */
    public readonly BigInteger $packets;

    /**
     * The total number of transmit errors detected by the device driver.
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
     * The number of collisions detected on the interface.
     */
    public readonly BigInteger $colls;

    /**
     * The number of carrier losses detected by the device driver.
     */
    public readonly BigInteger $carrier;

    /**
     * The number of compressed packets transmitted by the device driver.
     */
    public readonly BigInteger $compressed;


    public function __construct(
        BigInteger $bytes,
        BigInteger $packets,
        BigInteger $errs,
        BigInteger $drop,
        BigInteger $fifo,
        BigInteger $colls,
        BigInteger $carrier,
        BigInteger $compressed
    ) {
        $this->bytes = $bytes;
        $this->packets = $packets;
        $this->errs = $errs;
        $this->drop = $drop;
        $this->fifo = $fifo;
        $this->colls = $colls;
        $this->carrier = $carrier;
        $this->compressed = $compressed;
    }
}
