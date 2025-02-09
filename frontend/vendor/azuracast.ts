import {GlobalPermission, StationPermission} from "~/acl.ts";
import {PanelLayoutProps} from "~/components/PanelLayout.vue";

export interface AzuraCastStationConstants {
    id: number,
    name: string | null,
    shortName: string,
    isEnabled: boolean,
    hasStarted: boolean,
    needsRestart: boolean,
    timezone: string,
    offlineText: string | null,
    maxBitrate: number,
    maxMounts: number,
    maxHlsStreams: number,
    enablePublicPages: boolean,
    publicPageUrl: string,
    enableOnDemand: boolean,
    onDemandUrl: string,
    webDjUrl: string,
    enableRequests: boolean,
    features: Record<string, boolean>
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
    sidebarProps?: Record<string, any>,
    panelProps?: PanelLayoutProps,
    componentProps?: Record<string, any>,
    user?: AzuraCastUserConstants,
    station?: AzuraCastStationConstants,
}

let globalProps: AzuraCastConstants;

export function setGlobalProps(newGlobalProps: AzuraCastConstants): void {
    globalProps = newGlobalProps;
}

export function useAzuraCast(): AzuraCastConstants {
    return globalProps;
}

export function useAzuraCastPanelProps(): PanelLayoutProps {
    const {panelProps} = useAzuraCast();

    if (!panelProps) {
        throw new Error("Panel properties are undefined in this request.");
    }

    return panelProps;
}

export function useAzuraCastUser(): AzuraCastUserConstants {
    const {user} = useAzuraCast();

    if (!user) {
        throw Error("User is not logged in.");
    }

    return user;
}

export function useAzuraCastStation(): AzuraCastStationConstants {
    const {station} = useAzuraCast();

    if (!station) {
        throw Error("Station data is not provided in this request.");
    }

    return station;
}
