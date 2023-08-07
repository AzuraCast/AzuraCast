/* eslint-disable no-undef */

import {App, inject, InjectionKey} from "vue";

const globalPropsKey: InjectionKey<AzuraCastConstants> = Symbol() as InjectionKey<AzuraCastConstants>;

export function installGlobalProps(vueApp: App, globalProps: AzuraCastConstants): void {
    vueApp.provide(globalPropsKey, globalProps);
}

interface AzuraCastStationConstants {
    id: number | null,
    name: string | null,
    shortName: string | null,
    timezone: string
}

interface AzuraCastConstants {
    locale: string,
    localeShort: string,
    localeWithDashes: string,
    timeConfig: object,
    apiCsrf: string | null,
    enableAdvancedFeatures: boolean,
    panelProps: object | null,
    sidebarProps: object | null,
    componentProps: object | null,
    station: AzuraCastStationConstants | null,
}

export function useAzuraCast(): AzuraCastConstants {
    return inject(globalPropsKey);
}

export function useAzuraCastStation(): AzuraCastStationConstants {
    const {station} = useAzuraCast();
    return station;
}
