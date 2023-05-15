<?php

declare(strict_types=1);

namespace App\Enums;

use App\Entity\Station;

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
            self::Webhooks, self::Podcasts, self::RemoteRelays => true,
        };
    }
}
