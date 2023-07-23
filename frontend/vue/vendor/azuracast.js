/* eslint-disable no-undef */

export function useAzuraCast() {
    return {
        locale: App.locale ?? 'en_US',
        localeShort: App.locale_short ?? 'en',
        localeWithDashes: App.locale_with_dashes ?? 'en-US',
        localePaths: App.locale_paths ?? {},
        timeConfig: App.time_config ?? {},
        apiCsrf: App.api_csrf ?? null
    }
}
