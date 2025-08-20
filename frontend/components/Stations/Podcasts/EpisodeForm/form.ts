import {useAppRegle} from "~/vendor/regle.ts";
import {useResettableRef} from "~/functions/useResettableRef.ts";
import {defineStore} from "pinia";
import {required} from "@regle/rules";

export const useStationsPodcastEpisodesForm = defineStore(
    'form-stations-podcast-episodes',
    () => {
        const {record: form, reset} = useResettableRef({
            title: '',
            link: '',
            description: '',
            publish_at: null,
            explicit: false,
            season_number: null,
            episode_number: null,
            artwork_file: null,
            media_file: null
        });

        const {r$} = useAppRegle(
            form,
            {
                title: {required},
                description: {required},
            },
            {
                validationGroups: (fields) => ({
                    basicInfoTab: [
                        fields.title,
                        fields.link,
                        fields.description,
                        fields.publish_at,
                        fields.explicit,
                        fields.season_number,
                        fields.episode_number
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
