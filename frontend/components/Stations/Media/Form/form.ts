import {useAppRegle} from "~/vendor/regle.ts";
import {useResettableRef} from "~/functions/useResettableRef.ts";
import {defineStore} from "pinia";
import {required} from "@regle/rules";
import {CustomField} from "~/entities/ApiInterfaces.ts";
import {forEach} from "es-toolkit/compat";
import {InjectionKey, Ref} from "vue";
import injectRequired from "~/functions/injectRequired";
import {MediaExtraData, StationMediaRecord} from "~/entities/StationMedia.ts";

export const customFieldsKey = Symbol() as InjectionKey<Ref<CustomField[]>>;

export const useStationsMediaForm = defineStore(
    'form-stations-media',
    () => {
        const customFields: Ref<CustomField[]> = injectRequired(customFieldsKey);

        const {record, reset: resetRecord} = useResettableRef<MediaExtraData>({
            length: 0,
            length_text: '',
            links: {
                art: '',
                waveform: '',
                waveform_cache: '',
                play: ''
            }
        });

        const {record: form, reset} = useResettableRef<StationMediaRecord>(() => {
            const blankForm: StationMediaRecord = {
                path: "",
                title: null,
                artist: null,
                album: null,
                genre: null,
                lyrics: null,
                isrc: null,
                custom_fields: {},
                extra_metadata: {
                    amplify: null,
                    cross_start_next: null,
                    fade_in: null,
                    fade_out: null,
                    cue_in: null,
                    cue_out: null
                },
                playlists: [],
            };

            forEach(customFields.value.slice(), (field: CustomField) => {
                if (field.short_name) {
                    blankForm.custom_fields[field.short_name] = null;
                }
            });

            return blankForm;
        });

        const {r$} = useAppRegle(
            form,
            {
                path: {required},
            },
            {
                validationGroups: (fields) => ({
                    basicInfoTab: [
                        fields.path,
                        fields.title,
                        fields.artist,
                        fields.album,
                        fields.genre,
                        fields.lyrics,
                        fields.isrc,
                    ],
                    advancedSettingsTab: [
                        fields.extra_metadata.amplify,
                        fields.extra_metadata.cross_start_next,
                        fields.extra_metadata.fade_in,
                        fields.extra_metadata.fade_out,
                        fields.extra_metadata.cue_in,
                        fields.extra_metadata.cue_out
                    ]
                })
            }
        );

        const $reset = () => {
            reset();
            resetRecord();
            r$.$reset();
        }

        return {
            customFields,
            record,
            form,
            r$,
            $reset
        }
    }
);
