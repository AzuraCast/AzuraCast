/* eslint-disable no-undef */

export function useAzuraCast() {
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

export function useAzuraCastStation() {
    return {
        id: App.station?.id ?? null,
        name: App.station?.name ?? null,
        shortName: App.station?.shortName ?? null,
        timezone: App.station?.timezone ?? 'UTC'
    }
}
