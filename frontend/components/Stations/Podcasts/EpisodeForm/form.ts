import {useAppRegle} from "~/vendor/regle.ts";
import {useResettableRef} from "~/functions/useResettableRef.ts";
import {defineStore} from "pinia";
import {required} from "@regle/rules";
import {ApiPodcastEpisode} from "~/entities/ApiInterfaces.ts";
import {UploadResponseBody} from "~/components/Common/FlowUpload.vue";

export type PodcastEpisodeRecord = Required<Omit<
    ApiPodcastEpisode,
    | 'id'
    | 'links'
    | 'media'
    | 'created_at'
    | 'art'
    | 'storage_location_id'
    | 'description_short'
    | 'language_name'
    | 'has_custom_art'
    | 'art_updated_at'
    | 'publish_at'
    | 'is_published'
    | 'has_media'
    | 'playlist_media_id'
    | 'playlist_media'
>> & {
    publish_at: number | null,
    artwork_file: UploadResponseBody | null,
    media_file: UploadResponseBody | null,
}

export const useStationsPodcastEpisodesForm = defineStore(
    'form-stations-podcast-episodes',
    () => {
        const {record: form, reset} = useResettableRef<PodcastEpisodeRecord>({
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
