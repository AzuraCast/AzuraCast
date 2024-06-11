<?php

declare(strict_types=1);

namespace App\Service\CpuStats;

use Brick\Math\BigInteger;

final class CpuData
{
    public readonly string $name;

    public readonly bool $isDelta;

    /**
     * Time spent with normal processing in user mode.
     */
    public readonly int $user;

    /**
     * Time spent with niced processes in user mode.
     */
    public readonly int $nice;

    /**
     * Time spent running in kernel mode.
     */
    public readonly int $system;

    /**
     * Time spent in vacations twiddling thumbs.
     */
    public readonly int $idle;

    /**
     * Time spent waiting for I/O to completed. This is considered idle time too.
     *
     * since Kernel 2.5.41
     */
    public readonly ?int $iowait;

    /**
     * Time spent serving hardware interrupts. See the description of the intr line for more details.
     *
     * since 2.6.0
     */
    public readonly ?int $irq;

    /**
     * Time spent serving software interrupts.
     *
     * since 2.6.0
     */
    public readonly ?int $softirq;

    /**
     * Time stolen by other operating systems running in a virtual environment.
     *
     * since 2.6.11
     */
    public readonly ?int $steal;

    /**
     * Time spent for running a virtual CPU or guest OS under the control of the kernel.
     *
     * since 2.6.24
     */
    public readonly ?int $guest;

    public function __construct(
        string $name,
        bool $isDelta,
        int $user,
        int $nice,
        int $system,
        int $idle,
        ?int $iowait = null,
        ?int $irq = null,
        ?int $softirq = null,
        ?int $steal = null,
        ?int $guest = null,
    ) {
        $this->name = $name;
        $this->isDelta = $isDelta;
        $this->user = $user;
        $this->nice = $nice;
        $this->system = $system;
        $this->idle = $idle;
        $this->iowait = $iowait;
        $this->irq = $irq;
        $this->softirq = $softirq;
        $this->steal = $steal;
        $this->guest = $guest;
    }

    public static function fromCoreData(string $name, array $coreData): self
    {
        $user = (int)$coreData[0];
        $nice = (int)$coreData[1];
        $system = (int)$coreData[2];
        $idle = (int)$coreData[3];

        $iowait = null;
        if (isset($coreData[4])) {
            $iowait = (int)$coreData[4];
        }

        $irq = null;
        if (isset($coreData[5])) {
            $irq = (int)$coreData[5];
        }

        $softirq = null;
        if (isset($coreData[6])) {
            $softirq = (int)$coreData[6];
        }

        $steal = null;
        if (isset($coreData[7])) {
            $steal = (int)$coreData[7];
        }

        $guest = null;
        if (isset($coreData[8])) {
            $guest = (int)$coreData[8];
        }

        return new self(
            $name,
            false,
            $user,
            $nice,
            $system,
            $idle,
            $iowait,
            $irq,
            $softirq,
            $steal,
            $guest
        );
    }

    public function getTotalUsage(): BigInteger
    {
        return BigInteger::sum(
            $this->user,
            $this->nice,
            $this->system,
            $this->iowait ?? 0,
            $this->irq ?? 0,
            $this->softirq ?? 0,
            $this->steal ?? 0,
            $this->guest ?? 0
        );
    }

    public function getTotal(): BigInteger
    {
        return BigInteger::sum(
            $this->user,
            $this->nice,
            $this->system,
            $this->idle,
            $this->iowait ?? 0,
            $this->irq ?? 0,
            $this->softirq ?? 0,
            $this->steal ?? 0,
            $this->guest ?? 0
        );
    }
}
