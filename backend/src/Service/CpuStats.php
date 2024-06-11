<?php

declare(strict_types=1);

namespace App\Service;

use App\Service\CpuStats\CpuData;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;

final class CpuStats
{
    /**
     * @return CpuData[]
     */
    public static function getCurrentLoad(): array
    {
        $cpuStatsRaw = file('/proc/stat', FILE_IGNORE_NEW_LINES) ?: [];

        $cpuCoreData = [];

        foreach ($cpuStatsRaw as $statLine) {
            $lineData = preg_split('/\s+/', $statLine) ?: [];
            $lineName = array_shift($lineData) ?? '';

            if ($lineName === 'cpu') {
                $cpuCoreData[] = CpuData::fromCoreData('total', $lineData);
            } elseif (str_starts_with($lineName, 'cpu')) {
                $cpuCoreData[] = CpuData::fromCoreData($lineName, $lineData);
            }
        }

        return $cpuCoreData;
    }

    public static function calculateDelta(CpuData $current, CpuData $previous): CpuData
    {
        $name = $current->name;

        $user = $current->user - $previous->user;
        $nice = $current->nice - $previous->nice;
        $system = $current->system - $previous->system;
        $idle = $current->idle - $previous->idle;

        $iowait = null;
        if ($current->iowait !== null && $previous->iowait !== null) {
            $iowait = $current->iowait - $previous->iowait;
        }

        $irq = null;
        if ($current->irq !== null && $previous->irq !== null) {
            $irq = $current->irq - $previous->irq;
        }

        $softirq = null;
        if ($current->softirq !== null && $previous->softirq !== null) {
            $softirq = $current->softirq - $previous->softirq;
        }

        $steal = null;
        if ($current->steal !== null && $previous->steal !== null) {
            $steal = $current->steal - $previous->steal;
        }

        $guest = null;
        if ($current->guest !== null && $previous->guest !== null) {
            $guest = $current->guest - $previous->guest;
        }

        return new CpuData(
            $name,
            true,
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

    public static function getUsage(CpuData $delta): BigDecimal
    {
        $usage = $delta->getTotalUsage();
        $total = $delta->getTotal();

        return BigDecimal::of($usage)
            ->multipliedBy(100)
            ->dividedBy($total, 2, RoundingMode::HALF_UP);
    }

    public static function getIdle(CpuData $delta): BigDecimal
    {
        $idle = $delta->idle;
        $total = $delta->getTotal();

        return BigDecimal::of($idle)
            ->multipliedBy(100)
            ->dividedBy($total, 2, RoundingMode::HALF_UP);
    }

    public static function getIoWait(CpuData $delta): BigDecimal
    {
        $ioWait = $delta->iowait;
        $total = $delta->getTotal();

        return BigDecimal::of($ioWait ?? 0)
            ->multipliedBy(100)
            ->dividedBy($total, 2, RoundingMode::HALF_UP);
    }

    public static function getSteal(CpuData $delta): BigDecimal
    {
        $steal = $delta->steal;
        $total = $delta->getTotal();

        return BigDecimal::of($steal ?? 0)
            ->multipliedBy(100)
            ->dividedBy($total, 2, RoundingMode::HALF_UP);
    }
}
