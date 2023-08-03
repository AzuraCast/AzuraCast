/* eslint-disable no-undef */

interface AzuraCastConstants {
    locale: string,
    localeShort: string,
    localeWithDashes: string,
    localePaths: object,
    timeConfig: object,
    apiCsrf: string | null,
    enableAdvancedFeatures: boolean
}

export function useAzuraCast(): AzuraCastConstants {
    return {
        locale: App.locale ?? 'en_US',
        localeShort: App.locale_short ?? 'en',
        localeWithDashes: App.locale_with_dashes ?? 'en-US',
        localePaths: App.locale_paths ?? {},
        timeConfig: App.time_config ?? {},
        apiCsrf: App.api_csrf ?? null,
        enableAdvancedFeatures: App.enable_advanced_features ?? true
    }
}

interface AzuraCastStationConstants {
    id: number | null,
    name: string | null,
    shortName: string | null,
    timezone: string
}

export function useAzuraCastStation(): AzuraCastStationConstants {
    return {
        id: App.station?.id ?? null,
        name: App.station?.name ?? null,
        shortName: App.station?.shortName ?? null,
        timezone: App.station?.timezone ?? 'UTC'
    }
}
