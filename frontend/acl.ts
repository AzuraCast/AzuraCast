import {useAzuraCastStation, useAzuraCastUser} from "~/vendor/azuracast.ts";
import {get, includes} from "lodash";

export const StationPermissions = Object.freeze({
    All: 'administer all',
    View: 'view station management',
    Reports: 'view station reports',
    Logs: 'view station logs',
    Profile: 'manage station profile',
    Broadcasting: 'manage station broadcasting',
    Streamers: 'manage station streamers',
    MountPoints: 'manage station mounts',
    RemoteRelays: 'manage station remotes',
    Media: 'manage station media',
    Automation: 'manage station automation',
    WebHooks: 'manage station web hooks',
    Podcasts: 'manage station podcasts'
} as const);

export type StationPermission = typeof StationPermissions[keyof typeof StationPermissions];

export const GlobalPermissions = Object.freeze({
    All: 'administer all',
    View: 'view administration',
    Logs: 'view system logs',
    Settings: 'administer settings',
    ApiKeys: 'administer api keys',
    Stations: 'administer stations',
    CustomFields: 'administer custom fields',
    Backups: 'administer backups',
    StorageLocations: 'administer storage locations'
} as const);

export type GlobalPermission = typeof GlobalPermissions[keyof typeof GlobalPermissions];

export function userAllowed(permission: GlobalPermission): boolean {
    try {
        const {globalPermissions} = useAzuraCastUser();

        if (includes(globalPermissions, GlobalPermissions.All)) {
            return true;
        }

        return includes(globalPermissions, permission);
    } catch {
        return false;
    }
}

export function userAllowedForStation(permission: StationPermission, id: number | null = null): boolean {
    if (id === null) {
        try {
            const station = useAzuraCastStation();
            id = station.id;
        } catch {
            return false;
        }
    }

    if (userAllowed(GlobalPermissions.Stations)) {
        return true;
    }

    try {
        const {stationPermissions} = useAzuraCastUser();
        const thisStationPermissions = get(stationPermissions, id, []);

        if (includes(thisStationPermissions, StationPermissions.All)) {
            return true;
        }

        return includes(thisStationPermissions, permission);
    } catch {
        return false;
    }
}
