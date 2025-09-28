import {useAppRegle} from "~/vendor/regle.ts";
import {defineStore} from "pinia";
import {required} from "@regle/rules";
import {RemoteAdapters, StationRemote, StreamFormats} from "~/entities/ApiInterfaces.ts";
import {ref} from "vue";

export type StationRemotesRecord = Omit<
    Required<StationRemote>,
    | 'id'
    | 'listeners_unique'
    | 'listeners_total'
    | 'is_editable'
>

export const useStationsRemotesForm = defineStore(
    'form-stations-remotes',
    () => {
        const form = ref<StationRemotesRecord>({
            display_name: '',
            type: RemoteAdapters.Icecast,
            custom_listen_url: null,
            url: '',
            mount: null,
            admin_password: null,
            is_visible_on_public_pages: true,
            enable_autodj: false,
            autodj_format: StreamFormats.Mp3,
            autodj_bitrate: 128,
            source_port: null,
            source_mount: null,
            source_username: null,
            source_password: null,
            is_public: false
        });

        const {r$} = useAppRegle(
            form,
            {
                type: {required},
                url: {required},
            },
            {
                validationGroups: (fields) => ({
                    basicInfoTab: [
                        fields.display_name,
                        fields.type,
                        fields.custom_listen_url,
                        fields.url,
                        fields.mount,
                        fields.admin_password,
                        fields.is_visible_on_public_pages,
                    ],
                    autoDjTab: [
                        fields.enable_autodj,
                        fields.autodj_format,
                        fields.autodj_bitrate,
                        fields.source_port,
                        fields.source_mount,
                        fields.source_username,
                        fields.source_password,
                        fields.is_public
                    ],
                })
            }
        );

        const $reset = () => {
            r$.$reset({
                toOriginalState: true
            });
        }

        return {
            form,
            r$,
            $reset
        }
    }
);
