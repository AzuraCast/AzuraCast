/* eslint-disable no-undef */

import {GlobalPermission, StationPermission} from "~/acl.ts";

let globalProps: AzuraCastConstants;

export function setGlobalProps(newGlobalProps: AzuraCastConstants): void {
    globalProps = newGlobalProps;
}

export interface AzuraCastStationConstants {
    id: number | null,
    name: string | null,
    shortName: string | null,
    timezone: string | null,
    offlineText: string | null,
}

export interface AzuraCastUserConstants {
    id: number | null,
    displayName: string | null,
    globalPermissions: GlobalPermission[],
    stationPermissions: {
        [key: number]: StationPermission[]
    }
}

export interface AzuraCastConstants {
    locale: string,
    localeShort: string,
    localeWithDashes: string,
    timeConfig: object,
    apiCsrf: string | null,
    enableAdvancedFeatures: boolean,
    panelProps: object | null,
    sidebarProps: object | null,
    componentProps: object | null,
    user: AzuraCastUserConstants | null,
    station: AzuraCastStationConstants | null,
}

export function useAzuraCast(): AzuraCastConstants {
    return globalProps;
}

export function useAzuraCastUser(): AzuraCastUserConstants {
    const {user} = useAzuraCast();

    return (user !== null) ? user : {
        id: null,
        displayName: null,
        globalPermissions: [],
        stationPermissions: {}
    };
}

export function useAzuraCastStation(): AzuraCastStationConstants {
    const {station} = useAzuraCast();

    return (station !== null) ? station : {
        id: null,
        name: null,
        shortName: null,
        timezone: null,
        offlineText: null
    };
}
