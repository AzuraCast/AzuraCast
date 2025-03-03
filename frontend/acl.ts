import {useAzuraCastStation, useAzuraCastUser} from "~/vendor/azuracast.ts";
import {get, includes} from "lodash";
import {GlobalPermissions, StationPermissions} from "~/entities/ApiInterfaces.ts";

export function userAllowed(permission: GlobalPermissions): boolean {
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

export function userAllowedForStation(permission: StationPermissions, id: number | null = null): boolean {
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
