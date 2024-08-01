<?php

declare(strict_types=1);

namespace App\Enums;

use App\Entity\Station;
use App\Exception\StationUnsupportedException;

enum StationFeatures
{
    case CustomLiquidsoapConfig;
    case Media;
    case Sftp;
    case MountPoints;
    case RemoteRelays;
    case HlsStreams;
    case Streamers;
    case Webhooks;
    case Podcasts;
    case OnDemand;
    case Requests;

    public function supportedForStation(Station $station): bool
    {
        $backendEnabled = $station->getBackendType()->isEnabled();

        return match ($this) {
            self::Media, self::CustomLiquidsoapConfig => $backendEnabled,
            self::Streamers => $backendEnabled && $station->getEnableStreamers(),
            self::Sftp => $backendEnabled && $station->getMediaStorageLocation()->isLocal(),
            self::MountPoints => $station->getFrontendType()->supportsMounts(),
            self::HlsStreams => $backendEnabled && $station->getEnableHls(),
            self::Requests => $backendEnabled && $station->getEnableRequests(),
            self::OnDemand => $station->getEnableOnDemand(),
            self::Webhooks, self::Podcasts, self::RemoteRelays => true,
        };
    }

    /**
     * @param Station $station
     * @return void
     * @throws StationUnsupportedException
     */
    public function assertSupportedForStation(Station $station): void
    {
        if (!$this->supportedForStation($station)) {
            throw match ($this) {
                self::Requests => StationUnsupportedException::requests(),
                self::OnDemand => StationUnsupportedException::onDemand(),
                default => StationUnsupportedException::generic(),
            };
        }
    }
}
