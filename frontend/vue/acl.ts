import {useAzuraCastStation, useAzuraCastUser} from "~/vendor/azuracast.ts";
import {get, includes} from "lodash";

export enum StationPermission {
    All = 'administer all',
    View = 'view station management',
    Reports = 'view station reports',
    Logs = 'view station logs',
    Profile = 'manage station profile',
    Broadcasting = 'manage station broadcasting',
    Streamers = 'manage station streamers',
    MountPoints = 'manage station mounts',
    RemoteRelays = 'manage station remotes',
    Media = 'manage station media',
    Automation = 'manage station automation',
    WebHooks = 'manage station web hooks',
    Podcasts = 'manage station podcasts'
}

export enum GlobalPermission {
    All = 'administer all',
    View = 'view administration',
    Logs = 'view system logs',
    Settings = 'administer settings',
    ApiKeys = 'administer api keys',
    Stations = 'administer stations',
    CustomFields = 'administer custom fields',
    Backups = 'administer backups',
    StorageLocations = 'administer storage locations'
}

export function userAllowed(permission: GlobalPermission): boolean {
    const {globalPermissions} = useAzuraCastUser();

    if (includes(globalPermissions, GlobalPermission.All)) {
        return true;
    }

    return includes(globalPermissions, permission);
}

export function userAllowedForStation(permission: StationPermission, id: int | null = null): boolean {
    if (id === null) {
        const station = useAzuraCastStation();
        if ('id' in station) {
            id = station.id;
        } else {
            console.error('No station detected or provided.');
            return false;
        }
    }

    if (userAllowed(GlobalPermission.Stations)) {
        return true;
    }

    const {stationPermissions} = useAzuraCastUser();
    const thisStationPermissions = get(stationPermissions, id, []);

    if (includes(thisStationPermissions, StationPermission.All)) {
        return true;
    }

    return includes(thisStationPermissions, permission);
}
