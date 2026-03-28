<?php

declare(strict_types=1);

namespace App\Enums;

use App\Entity\Settings;
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

    public function supportedForStation(
        Station $station,
        Settings $settings
    ): bool {
        $backendEnabled = $station->backend_type->isEnabled();

        return match ($this) {
            self::Media => $backendEnabled,
            self::CustomLiquidsoapConfig => $backendEnabled && $settings->enable_liquidsoap_editing,
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
     * @param Settings $settings
     * @return void
     * @throws StationUnsupportedException
     */
    public function assertSupportedForStation(Station $station, Settings $settings): void
    {
        if (!$this->supportedForStation($station, $settings)) {
            throw match ($this) {
                self::Requests => StationUnsupportedException::requests(),
                self::OnDemand => StationUnsupportedException::onDemand(),
                default => StationUnsupportedException::generic(),
            };
        }
    }
}
