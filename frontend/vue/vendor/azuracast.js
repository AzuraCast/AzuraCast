export function useAzuraCast() {
    return {
        lang: {
            confirm: App.lang.confirm ?? 'Are you sure?',
            advanced: App.lang.advanced ?? 'Advanced'
        },
        locale: App.locale ?? 'en_US',
        localeShort: App.locale_short ?? 'en',
        localeWithDashes: App.locale_with_dashes ?? 'en-US',
        timeConfig: App.time_config ?? {},
        apiCsrf: App.api_csrf ?? null,
        theme: App.theme ?? 'light'
    }
}
