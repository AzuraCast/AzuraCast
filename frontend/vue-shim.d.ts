/// <reference types="vite/client" />

declare module 'vue' {
    interface ComponentCustomProperties {
        $gettext: (msgid: string, parameters?: {
            [key: string]: any;
        }, disableHtmlEscaping?: boolean) => string;
    }
}

export {}
