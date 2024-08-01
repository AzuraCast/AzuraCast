declare module '@vue/runtime-core' {
    interface ComponentCustomProperties {
        $gettext: (msgid: string, parameters?: {
            [key: string]: any;
        }, disableHtmlEscaping?: boolean) => string;
    }
}

export {}
