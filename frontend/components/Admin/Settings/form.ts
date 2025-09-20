import {useAppRegle} from "~/vendor/regle.ts";
import {required} from "@regle/rules";
import {IpSources, Settings} from "~/entities/ApiInterfaces.ts";
import {defineStore} from "pinia";
import { ref } from "vue";

type Form = Required<Pick<
    Settings,
    | 'base_url'
    | 'instance_name'
    | 'prefer_browser_url'
    | 'use_radio_proxy'
    | 'history_keep_days'
    | 'enable_static_nowplaying'
    | 'sync_disabled'
    | 'analytics'
    | 'always_use_ssl'
    | 'ip_source'
    | 'api_access_control'
    | 'check_for_updates'
    | 'acme_email'
    | 'acme_domains'
    | 'mail_enabled'
    | 'mail_sender_name'
    | 'mail_sender_email'
    | 'mail_smtp_host'
    | 'mail_smtp_port'
    | 'mail_smtp_secure'
    | 'mail_smtp_username'
    | 'mail_smtp_password'
    | 'avatar_service'
    | 'avatar_default_url'
    | 'use_external_album_art_in_apis'
    | 'use_external_album_art_when_processing_media'
    | 'last_fm_api_key'
>>

export const useAdminSettingsForm = defineStore(
    'form-admin-settings',
    () => {
        const form = ref<Form>({
            base_url: '',
            instance_name: '',
            prefer_browser_url: true,
            use_radio_proxy: true,
            history_keep_days: 7,
            enable_static_nowplaying: true,
            sync_disabled: false,
            analytics: null,
            always_use_ssl: false,
            ip_source: IpSources.Local,
            api_access_control: '*',
            check_for_updates: true,
            acme_email: '',
            acme_domains: '',
            mail_enabled: false,
            mail_sender_name: '',
            mail_sender_email: '',
            mail_smtp_host: '',
            mail_smtp_port: 0,
            mail_smtp_secure: false,
            mail_smtp_username: '',
            mail_smtp_password: '',
            avatar_service: 'gravatar',
            avatar_default_url: '',
            use_external_album_art_in_apis: false,
            use_external_album_art_when_processing_media: false,
            last_fm_api_key: ''
        });

        const {r$} = useAppRegle(
            form,
            {
                base_url: {required},
                history_keep_days: {required},
                analytics: {required},
            },
            {
                validationGroups: (fields) => ({
                    generalTab: [
                        fields.base_url,
                        fields.instance_name,
                        fields.prefer_browser_url,
                        fields.use_radio_proxy,
                        fields.history_keep_days,
                        fields.enable_static_nowplaying,
                    ],
                    securityPrivacyTab: [
                        fields.analytics,
                        fields.always_use_ssl,
                        fields.ip_source,
                        fields.api_access_control
                    ],
                    servicesTab: [
                        fields.check_for_updates,
                        fields.acme_email,
                        fields.acme_domains,
                        fields.mail_enabled,
                        fields.mail_sender_name,
                        fields.mail_sender_email,
                        fields.mail_smtp_host,
                        fields.mail_smtp_port,
                        fields.mail_smtp_secure,
                        fields.mail_smtp_username,
                        fields.mail_smtp_password,
                        fields.avatar_service,
                        fields.avatar_default_url,
                        fields.use_external_album_art_in_apis,
                        fields.use_external_album_art_when_processing_media,
                        fields.last_fm_api_key
                    ],
                    debuggingTab: [
                        fields.sync_disabled
                    ]
                })
            }
        );

        const $reset = () => {
            r$.$reset({toOriginalState: true});
        }

        return {
            form,
            r$,
            $reset
        }
    }
);
