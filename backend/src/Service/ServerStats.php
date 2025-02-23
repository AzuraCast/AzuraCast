<?php

declare(strict_types=1);

namespace App\Service;

use App\Service\ServerStats\CpuData;
use App\Service\ServerStats\MemoryData;
use App\Service\ServerStats\NetworkData;
use App\Service\ServerStats\NetworkData\Received;
use App\Service\ServerStats\NetworkData\Transmitted;
use Brick\Math\BigDecimal;

final class ServerStats
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

    public static function calculateCpuDelta(CpuData $current, CpuData $previous): CpuData
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

    public static function getMemoryUsage(): MemoryData
    {
        $meminfoRaw = file('/proc/meminfo', FILE_IGNORE_NEW_LINES) ?: [];
        $meminfo = [];

        foreach ($meminfoRaw as $line) {
            if (!str_contains($line, ':')) {
                continue;
            }

            [$key, $val] = explode(':', $line);
            $meminfo[$key] = trim($val);
        }

        return MemoryData::fromMeminfo($meminfo);
    }

    public static function getNetworkUsage(): array
    {
        $networkRaw = file('/proc/net/dev', FILE_IGNORE_NEW_LINES) ?: [];
        $currentTimestamp = microtime(true);
        $interfaces = [];

        foreach ($networkRaw as $lineNumber => $line) {
            if ($lineNumber <= 1) {
                continue;
            }

            [$interfaceName, $interfaceData] = explode(':', $line);
            $interfaceName = trim($interfaceName);
            $interfaceData = preg_split('/\s+/', trim($interfaceData)) ?: [];

            $interfaces[] = NetworkData::fromInterfaceData(
                $interfaceName,
                BigDecimal::of($currentTimestamp),
                $interfaceData
            );
        }

        return $interfaces;
    }

    public static function calculateNetworkDelta(NetworkData $current, NetworkData $previous): NetworkData
    {
        $interfaceName = $current->interfaceName;

        $received = self::calculateReceivedDelta($current->received, $previous->received);
        $transmitted = self::calculateTransmittedDelta($current->transmitted, $previous->transmitted);

        return new NetworkData(
            $interfaceName,
            $current->time->minus($previous->time),
            $received,
            $transmitted,
            true
        );
    }

    public static function calculateReceivedDelta(Received $current, Received $previous): Received
    {
        return new Received(
            $current->bytes->minus($previous->bytes),
            $current->packets->minus($previous->packets),
            $current->errs->minus($previous->errs),
            $current->drop->minus($previous->drop),
            $current->fifo->minus($previous->fifo),
            $current->frame->minus($previous->frame),
            $current->compressed->minus($previous->compressed),
            $current->multicast->minus($previous->multicast)
        );
    }

    public static function calculateTransmittedDelta(Transmitted $current, Transmitted $previous): Transmitted
    {
        return new Transmitted(
            $current->bytes->minus($previous->bytes),
            $current->packets->minus($previous->packets),
            $current->errs->minus($previous->errs),
            $current->drop->minus($previous->drop),
            $current->fifo->minus($previous->fifo),
            $current->colls->minus($previous->colls),
            $current->carrier->minus($previous->carrier),
            $current->compressed->minus($previous->compressed)
        );
    }
}
