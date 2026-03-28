import {useAzuraCastUser} from "~/vendor/azuracast.ts";
import {useUserAllowed} from "~/functions/useUserAllowed.ts";
import {useStationId} from "~/functions/useStationQuery.ts";
import {GlobalPermissions, StationPermissions} from "~/entities/ApiInterfaces.ts";

export function useUserAllowedForStation() {
    const {permissions} = useAzuraCastUser();
    const {userAllowed} = useUserAllowed();
    const stationId = useStationId();

    return {
        userAllowedForStation: (permission: StationPermissions) => {
            try {
                if (userAllowed(GlobalPermissions.Stations)) {
                    return true;
                }

                const thisStationPermissions = permissions.station.find(
                    (row) => row.id === stationId.value
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
    };
}
