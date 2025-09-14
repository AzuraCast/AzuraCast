import {ApiPodcast, ApiPodcastCategory, PodcastBrandingConfiguration} from "~/entities/ApiInterfaces.ts";
import {UploadResponseBody} from "~/components/Common/FlowUpload.vue";

export type PodcastRequired = Required<
    Omit<
        ApiPodcast,
        | 'branding_config'
        | 'categories'
    > & {
    branding_config: Required<PodcastBrandingConfiguration>,
    categories: Required<ApiPodcastCategory>[]
}
>

export type PodcastRecord = Omit<
    PodcastRequired,
    | 'id'
    | 'links'
    | 'art'
    | 'art_updated_at'
    | 'has_custom_art'
    | 'episodes'
    | 'storage_location_id'
    | 'description_short'
    | 'language_name'
    | 'guid'
    | 'is_published'
    | 'categories'
> & {
    categories: string[]
    artwork_file: UploadResponseBody | null
}
