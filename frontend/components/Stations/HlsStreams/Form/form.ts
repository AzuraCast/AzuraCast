import {useAppRegle} from "~/vendor/regle.ts";
import {useResettableRef} from "~/functions/useResettableRef.ts";
import {HlsStreamProfiles} from "~/entities/ApiInterfaces.ts";
import {defineStore} from "pinia";
import {required} from "@regle/rules";

export const useStationsHlsStreamsForm = defineStore(
    'form-stations-hls-streams',
    () => {
        const {record: form, reset} = useResettableRef({
            name: '',
            format: HlsStreamProfiles.AacLowComplexity,
            bitrate: 128
        });

        const {r$} = useAppRegle(
            form,
            {
                name: {required},
                format: {required},
                bitrate: {required}
            },
            {
                validationGroups: (fields) => ({
                    basicInfoTab: [
                        fields.name,
                        fields.format,
                        fields.bitrate
                    ],
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
