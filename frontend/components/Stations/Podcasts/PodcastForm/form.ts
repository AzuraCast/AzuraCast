import {useAppRegle} from "~/vendor/regle.ts";
import {useResettableRef} from "~/functions/useResettableRef.ts";
import {defineStore} from "pinia";
import {required} from "@regle/rules";
import {ApiPodcast} from "~/entities/ApiInterfaces.ts";
import {UploadResponseBody} from "~/components/Common/FlowUpload.vue";

export type PodcastRecord = Omit<
    Required<ApiPodcast>,
    'id'
> & {
    artwork_file: UploadResponseBody | null
}

export const useStationsPodcastsForm = defineStore(
    'form-stations-podcasts',
    () => {
        const {record: form, reset} = useResettableRef<PodcastRecord>({
            title: '',
            link: '',
            description: '',
            language: 'en',
            author: '',
            email: '',
            categories: [],
            is_enabled: true,
            branding_config: {
                public_custom_html: '',
                enable_op3_prefix: false
            },
            source: 'manual',
            playlist_id: null,
            playlist_auto_publish: true,
            artwork_file: null,
        });

        const {r$} = useAppRegle(
            form,
            {
                title: {required},
                description: {required},
                language: {required},
                categories: {required},
                source: {required},
            },
            {
                validationGroups: (fields) => ({
                    basicInfoTab: [
                        fields.title,
                        fields.link,
                        fields.description,
                        fields.language,
                        fields.author,
                        fields.email,
                        fields.categories,
                        fields.is_enabled
                    ],
                    brandingTab: [
                        fields.branding_config.public_custom_html,
                        fields.branding_config.enable_op3_prefix
                    ],
                    sourceTab: [
                        fields.source,
                        fields.playlist_id,
                        fields.playlist_auto_publish,
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
