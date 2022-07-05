<?php

declare(strict_types=1);

namespace App\Enums;

enum StationPermissions: string implements PermissionInterface
{
    case All = 'administer all';
    case View = 'view station management';
    case Reports = 'view station reports';
    case Logs = 'view station logs';
    case Profile = 'manage station profile';
    case Broadcasting = 'manage station broadcasting';
    case Streamers = 'manage station streamers';
    case MountPoints = 'manage station mounts';
    case RemoteRelays = 'manage station remotes';
    case Media = 'manage station media';
    case Automation = 'manage station automation';
    case WebHooks = 'manage station web hooks';
    case Podcasts = 'manage station podcasts';

    public function getName(): string
    {
        return match ($this) {
            self::All => __('All Permissions'),
            self::View => __('View Station Page'),
            self::Reports => __('View Station Reports'),
            self::Logs => __('View Station Logs'),
            self::Profile => __('Manage Station Profile'),
            self::Broadcasting => __('Manage Station Broadcasting'),
            self::Streamers => __('Manage Station Streamers'),
            self::MountPoints => __('Manage Station Mount Points'),
            self::RemoteRelays => __('Manage Station Remote Relays'),
            self::Media => __('Manage Station Media'),
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
