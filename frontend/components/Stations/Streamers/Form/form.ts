import {useAppScopedRegle} from "~/vendor/regle.ts";
import {useResettableRef} from "~/functions/useResettableRef.ts";
import {defineStore} from "pinia";
import {required, requiredIf} from "@regle/rules";
import {ref} from "vue";
import {StationStreamer} from "~/entities/ApiInterfaces.ts";
import {UploadResponseBody} from "~/components/Common/FlowUpload.vue";

export type StationStreamersRecord = Omit<
    Required<StationStreamer>,
    | 'id'
    | 'reactivate_at'
> & {
    artwork_file: UploadResponseBody | null
}

export const useStationsStreamersForm = defineStore(
    'form-stations-streamers',
    () => {
        const isEditMode = ref(false);

        const setEditMode = (newValue: boolean) => {
            isEditMode.value = newValue;
        };

        const {record: form, reset} = useResettableRef<StationStreamersRecord>({
            streamer_username: null,
            streamer_password: null,
            display_name: null,
            comments: null,
            is_active: true,
            enforce_schedule: false,
            artwork_file: null,
            schedule_items: []
        });

        const {r$} = useAppScopedRegle(
            form,
            {
                streamer_username: {required},
                streamer_password: {
                    required: requiredIf(() => !isEditMode.value)
                },
            },
            {
                namespace: 'stations-streamers',
                validationGroups: (fields) => ({
                    basicInfoTab: [
                        fields.streamer_username,
                        fields.streamer_password,
                        fields.display_name,
                        fields.comments,
                        fields.is_active,
                        fields.enforce_schedule,
                    ],
                })
            }
        );

        const $reset = () => {
            reset();
            r$.$reset();
        }

        return {
            isEditMode,
            setEditMode,
            form,
            r$,
            $reset
        }
    }
);
