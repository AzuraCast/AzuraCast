import {useAzuraCastUser} from "~/vendor/azuracast.ts";
import {GlobalPermissions, StationPermissions} from "~/entities/ApiInterfaces.ts";
import {useStationId} from "~/functions/useStationQuery.ts";

export function userAllowed(permission: GlobalPermissions): boolean {
    try {
        const {permissions} = useAzuraCastUser();

        if (permissions.global.indexOf(GlobalPermissions.All) !== -1) {
            return true;
        }

        return permissions.global.indexOf(permission) !== -1;
    } catch {
        return false;
    }
}

export function userAllowedForStation(permission: StationPermissions, id: number | null = null): boolean {
    if (id === null) {
        try {
            const stationId = useStationId();
            id = stationId.value;
        } catch {
            return false;
        }
    }

    if (userAllowed(GlobalPermissions.Stations)) {
        return true;
    }

    try {
        const {permissions} = useAzuraCastUser();

        const thisStationPermissions = permissions.station.find(
            (row) => row.id === id
        );

        if (thisStationPermissions === undefined) {
            return false;
        }

        if (thisStationPermissions.permissions.indexOf(StationPermissions.All) !== -1) {
            return true;
        }

        return thisStationPermissions.permissions.indexOf(permission) !== -1;
    } catch {
        return false;
    }
}
