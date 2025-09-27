import {useAppRegle} from "~/vendor/regle.ts";
import {useResettableRef} from "~/functions/useResettableRef.ts";
import {defineStore} from "pinia";
import {required} from "@regle/rules";
import {ref} from "vue";
import {PodcastEpisodeExtraData, PodcastEpisodeRecord} from "~/entities/Podcasts.ts";

export const useStationsPodcastEpisodesForm = defineStore(
    'form-stations-podcast-episodes',
    () => {
        const {record, reset: resetRecord} = useResettableRef<PodcastEpisodeExtraData>({
            has_custom_art: false,
            art: null,
            has_media: false,
            media: null,
            links: {
                art: '',
                media: '',
                download: '',
            }
        });

        const form = ref<PodcastEpisodeRecord>({
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
            resetRecord();
            r$.$reset({
                toOriginalState: true
            });
        }

        return {
            form,
            record,
            r$,
            $reset
        }
    }
);
