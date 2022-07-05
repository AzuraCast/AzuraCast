<?php

declare(strict_types=1);

namespace App\Enums;

enum GlobalPermissions: string implements PermissionInterface
{
    case All = 'administer all';
    case View = 'view administration';
    case Logs = 'view system logs';
    case Settings = 'administer settings';
    case ApiKeys = 'administer api keys';
    case Stations = 'administer stations';
    case CustomFields = 'administer custom fields';
    case Backups = 'administer backups';
    case StorageLocations = 'administer storage locations';

    public function getName(): string
    {
        return match ($this) {
            self::All => __('All Permissions'),
            self::View => __('View Administration Page'),
            self::Logs => __('View System Logs'),
            self::Settings => __('Administer Settings'),
            self::ApiKeys => __('Administer API Keys'),
            self::Stations => __('Administer Stations'),
            self::CustomFields => __('Administer Custom Fields'),
            self::Backups => __('Administer Backups'),
            self::StorageLocations => __('Administer Storage Locations'),
        };
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function needsStation(): bool
    {
        return false;
    }
}
