<?php

declare(strict_types=1);

namespace App\Enums;

enum StationPermissions: string implements PermissionInterface
{
    case All = 'administer all';
    case View = 'view station management';
    case Reports = 'view station reports';
    case ReportsStatistics = 'view station reports station statistics';
    case ReportsListeners = 'view station reports listeners';
    case ReportsSongRequests = 'view station reports song requests';
    case ReportsSongTimeline = 'view station reports song playback timeline';
    case ReportsSoundExchange = 'manage station reports soundexchange';
    case Logs = 'view station logs';
    case Profile = 'manage station profile';
    case Broadcasting = 'manage station broadcasting';
    case BroadcastingFallbackFile = 'manage station broadcasting custom fallback file';
    case BroadcastingLiquidsoapConfig = 'manage station broadcasting edit liquidsoap config';
    case Streamers = 'manage station streamers';
    case MountPoints = 'manage station mounts';
    case RemoteRelays = 'manage station remotes';
    case Media = 'manage station media';
    case MediaImportExport = 'manage station media import export';
    case Automation = 'manage station automation';
    case WebHooks = 'manage station web hooks';
    case Podcasts = 'manage station podcasts';

    public function getName(): string
    {
        return match ($this) {
            self::All => __('All Permissions'),
            self::View => __('View Station Page'),
            self::Reports => __('View Station Reports (All)'),
            self::ReportsStatistics => __('View Station Reports / Station Statistics'),
            self::ReportsListeners => __('View Station Reports / Listeners'),
            self::ReportsSongRequests => __('View Station Reports / Song Requests'),
            self::ReportsSongTimeline => __('View Station Reports / Song Playback Timeline'),
            self::ReportsSoundExchange => __('View Station Reports / SoundExchange'),
            self::Logs => __('View Station Logs'),
            self::Profile => __('Manage Station Profile'),
            self::Broadcasting => __('Manage Station Broadcasting'),
            self::BroadcastingFallbackFile => __('Manage Station Broadcasting / Custom Fallback File'),
            self::BroadcastingLiquidsoapConfig => __('Manage Station Broadcasting / Edit Liquidsoap Configuration'),
            self::Streamers => __('Manage Station Streamers'),
            self::MountPoints => __('Manage Station Mount Points'),
            self::RemoteRelays => __('Manage Station Remote Relays'),
            self::Media => __('Manage Station Media'),
            self::MediaImportExport => __('Manage Station Media / CSV Import & Export'),
            self::Automation => __('Manage Station Automation'),
            self::WebHooks => __('Manage Station Web Hooks'),
            self::Podcasts => __('Manage Station Podcasts'),
        };
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function needsStation(): bool
    {
        return true;
    }
}
