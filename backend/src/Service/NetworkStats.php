<?php

declare(strict_types=1);

namespace App\Service;

use App\Service\NetworkStats\NetworkData;
use App\Service\NetworkStats\NetworkData\Received;
use App\Service\NetworkStats\NetworkData\Transmitted;
use Brick\Math\BigDecimal;

final class NetworkStats
{
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

    public static function calculateDelta(NetworkData $current, NetworkData $previous): NetworkData
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
