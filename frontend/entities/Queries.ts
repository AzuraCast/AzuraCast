import {useStationId} from "~/functions/useStationQuery.ts";
import {ComputedRef} from "vue";

export enum QueryKeys {
    Dashboard = 'Dashboard',

    StationGroup = 'Station',
    StationGlobals = 'StationGlobals',
    StationHlsStreams = 'StationHlsStreams',
    StationLogs = 'StationLogs',
    StationMedia = 'StationMedia',
    StationMounts = 'StationMounts',
    StationPlaylists = 'StationPlaylists',
    StationPodcasts = 'StationPodcasts',
    StationProfile = 'StationProfile',
    StationQueue = 'StationQueue',
    StationRemotes = 'StationRemotes',
    StationReports = 'StationReports',
    StationSftpUsers = 'StationSftpUsers',
    StationStreamers = 'StationStreamers',
    StationWebhooks = 'StationWebhooks',

    AccountApiKeys = 'AccountApiKeys',
    AccountIndex = 'AccountIndex',
    AccountPasskeys = 'AccountPasskeys',

    PublicOnDemand = 'PublicOnDemand',
    PublicPodcasts = 'PublicPodcasts',
    PublicRequests = 'PublicRequests',

    AdminApiKeys = 'AdminApiKeys',
    AdminAuditLog = 'AdminAuditLog',
    AdminBackups = 'AdminBackups',
    AdminCustomFields = 'AdminCustomFields',
    AdminDebug = 'AdminDebug',
    AdminIndex = 'AdminIndex',
    AdminPermissions = 'AdminPermissions',
    AdminRelays = 'AdminRelays',
    AdminSettings = 'AdminSettings',
    AdminStations = 'AdminStations',
    AdminStorageLocations = 'AdminStorageLocations',
    AdminUpdates = 'AdminUpdates',
    AdminUsers = 'AdminUsers',
}

export const queryKeyWithStation = (
    suffix?: unknown[],
    id?: ComputedRef<number | null>
): unknown[] => {
    id ??= useStationId();

    const newQueryKeys: unknown[] = [
        QueryKeys.StationGroup,
        {
            station: id
        }
    ];
    newQueryKeys.push({
        station: id
    });

    if (suffix) {
        newQueryKeys.push(...suffix);
    }

    return newQueryKeys;
}
