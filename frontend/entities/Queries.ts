import {useAzuraCastStation} from "~/vendor/azuracast.ts";

export enum QueryKeys {
    StationHlsStreams = 'StationHlsStreams',
    StationMedia = 'StationMedia',
    StationMounts = 'StationMounts',
    StationPlaylists = 'StationPlaylists',
    StationPodcasts = 'StationPodcasts',
    StationQueue = 'StationQueue',
    StationRemotes = 'StationRemotes',
    StationReports = 'StationReports',
    StationSftpUsers = 'StationSftpUsers',
    StationStreamers = 'StationStreamers',
    StationWebhooks = 'StationWebhooks',

    AccountApiKeys = 'AccountApiKeys',
    AccountPasskeys = 'AccountPasskeys',

    PublicOnDemand = 'PublicOnDemand',
    PublicPodcasts = 'PublicPodcasts',
    PublicRequests = 'PublicRequests',

    AdminApiKeys = 'AdminApiKeys',
    AdminAuditLog = 'AdminAuditLog',
    AdminBackups = 'AdminBackups',
    AdminCustomFields = 'AdminCustomFields',
    AdminDebug = 'AdminDebug',
    AdminPermissions = 'AdminPermissions',
    AdminRelays = 'AdminRelays',
    AdminStations = 'AdminStations',
    AdminStorageLocations = 'AdminStorageLocations',
    AdminUsers = 'AdminUsers',
}

export const queryKeyWithStation = (
    prefix: unknown[],
    suffix?: unknown[]
): unknown[] => {
    const {id} = useAzuraCastStation();

    const newQueryKeys = [...prefix];
    newQueryKeys.push({station: id});

    if (suffix) {
        newQueryKeys.push(...suffix);
    }

    return newQueryKeys;
}
