import {useAppRegle} from "~/vendor/regle.ts";
import {useResettableRef} from "~/functions/useResettableRef.ts";
import {defineStore} from "pinia";
import {required} from "@regle/rules";
import {StationMount, StreamFormats} from "~/entities/ApiInterfaces.ts";
import {DeepRequired} from "utility-types";
import {UploadResponseBody} from "~/components/Common/FlowUpload.vue";

export type StationMountRecord = Omit<
    DeepRequired<StationMount>, 'id' | 'listeners_unique' | 'listeners_total'
> & {
    intro_file: UploadResponseBody | null
};

export const useStationsMountsForm = defineStore(
    'form-stations-mounts',
    () => {
        const {record: form, reset} = useResettableRef<StationMountRecord>({
            name: null,
            display_name: null,
            is_visible_on_public_pages: true,
            is_default: false,
            relay_url: null,
            is_public: true,
            max_listener_duration: 0,
            authhash: null,
            fallback_mount: '/error.mp3',
            intro_file: null,
            enable_autodj: true,
            autodj_format: StreamFormats.Mp3,
            autodj_bitrate: 128,
            custom_listen_url: null,
            frontend_config: null,
        });

        const {r$} = useAppRegle(
            form,
            {
                name: {required},
                max_listener_duration: {required},
            },
            {
                validationGroups: (fields) => ({
                    basicInfoTab: [
                        fields.name,
                        fields.display_name,
                        fields.is_visible_on_public_pages,
                        fields.is_default,
                        fields.relay_url,
                        fields.is_public,
                        fields.max_listener_duration,
                        fields.authhash,
                        fields.fallback_mount
                    ],
                    autoDjTab: [
                        fields.enable_autodj,
                        fields.autodj_format,
                        fields.autodj_bitrate,
                    ],
                    advancedTab: [
                        fields.custom_listen_url,
                        fields.frontend_config,
                    ]
                })
            }
        );

        const $reset = () => {
            reset();
            r$.$reset();
        }

        return {
            form,
            r$,
            $reset
        }
    }
);
