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
        $backendEnabled = $station->backend_type->isEnabled();

        return match ($this) {
            self::Media, self::CustomLiquidsoapConfig => $backendEnabled,
            self::Streamers => $backendEnabled && $station->enable_streamers,
            self::Sftp => $backendEnabled && $station->media_storage_location->adapter->isLocal(),
            self::MountPoints => $station->frontend_type->supportsMounts(),
            self::HlsStreams => $backendEnabled && $station->enable_hls,
            self::Requests => $backendEnabled && $station->enable_requests,
            self::OnDemand => $station->enable_on_demand,
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
