import {useAzuraCastUser} from "~/vendor/azuracast.ts";
import {find, includes} from "es-toolkit/compat";
import {GlobalPermissions, StationPermissions} from "~/entities/ApiInterfaces.ts";
import {useStationId} from "~/functions/useStationQuery.ts";

export function userAllowed(permission: GlobalPermissions): boolean {
    try {
        const {permissions} = useAzuraCastUser();

        if (includes(permissions.global, GlobalPermissions.All)) {
            return true;
        }

        return includes(permissions.global, permission);
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

        const thisStationPermissions = find(
            permissions.station,
            (row) => row.id === id
        );

        if (thisStationPermissions === undefined) {
            return false;
        }

        if (includes(thisStationPermissions.permissions, StationPermissions.All)) {
            return true;
        }

        return includes(thisStationPermissions.permissions, permission);
    } catch {
        return false;
    }
}
