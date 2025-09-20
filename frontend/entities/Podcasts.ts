import {
    ApiPodcast,
    ApiPodcastCategory,
    ApiPodcastEpisode,
    HasLinks,
    PodcastBrandingConfiguration
} from "~/entities/ApiInterfaces.ts";
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

export type PodcastExtraData = Required<HasLinks> & {
    has_custom_art: boolean,
    art: string | null
};

export type PodcastResponseBody = Omit<PodcastRecord & PodcastExtraData, 'categories'> & {
    categories: Required<ApiPodcastCategory>[]
};

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

export type PodcastEpisodeExtraData = Required<HasLinks> & {
    has_custom_art: boolean,
    art: string | null,
    has_media: boolean,
    media: string | null,
}

export type PodcastEpisodeResponseBody = PodcastEpisodeRecord & PodcastEpisodeExtraData
