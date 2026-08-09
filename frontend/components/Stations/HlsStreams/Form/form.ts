import { required } from "@regle/rules";
import { defineStore } from "pinia";
import { ref } from "vue";
import {
    HlsStreamProfiles,
    StationHlsStream,
} from "~/entities/ApiInterfaces.ts";
import { useAppRegle } from "~/vendor/regle.ts";

export type HlsStreamRecord = Omit<Required<StationHlsStream>, "id">;

export const useStationsHlsStreamsForm = defineStore(
    "form-stations-hls-streams",
    () => {
        const form = ref<HlsStreamRecord>({
            name: "",
            format: HlsStreamProfiles.AacLowComplexity,
            bitrate: 128,
        });

        const { r$ } = useAppRegle(
            form,
            {
                name: { required },
                format: { required },
                bitrate: { required },
            },
            {
                validationGroups: (fields) => ({
                    basicInfoTab: [fields.name, fields.format, fields.bitrate],
                }),
            },
        );

        const $reset = () => {
            r$.$reset({ toOriginalState: true });
        };

        return {
            form,
            r$,
            $reset,
        };
    },
);
