import {useAppScopedRegle} from "~/vendor/regle.ts";
import {useResettableRef} from "~/functions/useResettableRef.ts";
import {defineStore} from "pinia";
import {required, requiredIf} from "@regle/rules";
import {ref} from "vue";
import {HasLinks, StationStreamer} from "~/entities/ApiInterfaces.ts";
import {UploadResponseBody} from "~/components/Common/FlowUpload.vue";

export type StationStreamersRecord = Omit<
    Required<StationStreamer>,
    | 'id'
    | 'reactivate_at'
> & {
    artwork_file: UploadResponseBody | null
}

export type StationStreamersExtraData = Required<HasLinks> & {
    has_custom_art: boolean
};

export type StationStreamersResponseBody = StationStreamersRecord & StationStreamersExtraData;

export const useStationsStreamersForm = defineStore(
    'form-stations-streamers',
    () => {
        const isEditMode = ref(false);

        const setEditMode = (newValue: boolean) => {
            isEditMode.value = newValue;
        };

        const {record, reset: resetRecord} = useResettableRef<StationStreamersExtraData>({
            has_custom_art: false,
            links: {
                art: '',
            }
        });

        const form = ref<StationStreamersRecord>({
            streamer_username: '',
            streamer_password: '',
            display_name: '',
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
            resetRecord();
            r$.$reset({
                toOriginalState: true
            });
        }

        return {
            isEditMode,
            setEditMode,
            form,
            record,
            r$,
            $reset
        }
    }
);
