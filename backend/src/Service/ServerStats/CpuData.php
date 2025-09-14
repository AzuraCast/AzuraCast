<?php

declare(strict_types=1);

namespace App\Service\ServerStats;

use Brick\Math\BigDecimal;
use Brick\Math\BigInteger;
use Brick\Math\RoundingMode;

final readonly class CpuData
{
    public function __construct(
        public string $name,
        public bool $isDelta,
        // Time spent with normal processing in user mode.
        public int $user,
        // Time spent with niced processes in user mode.
        public int $nice,
        // Time spent running in kernel mode.
        public int $system,
        // Time spent in vacations twiddling thumbs.
        public int $idle,
        // Time spent waiting for I/O to complete. This is considered idle time too.
        public ?int $iowait = null,
        // Time spent serving hardware interrupts. See the description of the intr line for more details.
        public ?int $irq = null,
        // Time spent serving software interrupts.
        public ?int $softirq = null,
        // Time stolen by other operating systems running in a virtual environment.
        public ?int $steal = null,
        // Time spent for running a virtual CPU or guest OS under the control of the kernel.
        public ?int $guest = null,
    ) {
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

    public function getUsage(): BigDecimal
    {
        assert($this->isDelta);

        $usage = $this->getTotalUsage();
        $total = $this->getTotal();

        return BigDecimal::of($usage)
            ->multipliedBy(100)
            ->dividedBy($total, 2, RoundingMode::HALF_UP);
    }

    public function getIdle(): BigDecimal
    {
        assert($this->isDelta);

        $idle = $this->idle;
        $total = $this->getTotal();

        return BigDecimal::of($idle)
            ->multipliedBy(100)
            ->dividedBy($total, 2, RoundingMode::HALF_UP);
    }

    public function getIoWait(): BigDecimal
    {
        assert($this->isDelta);

        $ioWait = $this->iowait;
        $total = $this->getTotal();

        return BigDecimal::of($ioWait ?? 0)
            ->multipliedBy(100)
            ->dividedBy($total, 2, RoundingMode::HALF_UP);
    }

    public function getSteal(): BigDecimal
    {
        assert($this->isDelta);

        $steal = $this->steal;
        $total = $this->getTotal();

        return BigDecimal::of($steal ?? 0)
            ->multipliedBy(100)
            ->dividedBy($total, 2, RoundingMode::HALF_UP);
    }
}
