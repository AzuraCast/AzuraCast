import {
    ApiFileList,
    ApiFileListDir,
    ApiStationMedia,
    ApiStationMediaPlaylist,
    ApiStationsVueFilesProps,
    CustomField
} from "~/entities/ApiInterfaces.ts";

export type StationMediaMetadata = {
    amplify: number | null,
    cross_start_next: number | null,
    fade_in: number | null,
    fade_out: number | null,
    cue_in: number | null,
    cue_out: number | null
}

export type StationMediaRecord =
    Required<Omit<
        ApiStationMedia,
        | 'id'
        | 'length'
        | 'length_text'
        | 'text'
        | 'links'
        | 'unique_id'
        | 'song_id'
        | 'mtime'
        | 'uploaded_at'
        | 'art'
        | 'art_updated_at'
        | 'custom_fields'
        | 'extra_metadata'
    >> & {
    custom_fields: Record<string, any>,
    extra_metadata: StationMediaMetadata
}

export type MediaExtraData = Required<Pick<
    ApiStationMedia,
    | 'length'
    | 'length_text'
    | 'links'
>>

export type MediaHttpResponse = StationMediaRecord & MediaExtraData;

export type StationsVueFilesPropsRequired = Required<
    Omit<
        ApiStationsVueFilesProps,
        | 'customFields'
    > & {
    customFields: Required<CustomField>[]
}
>

export type FileListRequired = Required<
    Omit<
        ApiFileList,
        | 'media'
        | 'dir'
    > & {
    media: Required<
        Omit<
            ApiStationMedia,
            | 'playlists'
        > & {
        playlists: Required<ApiStationMediaPlaylist>[]
    }
    > | null,
    dir: Required<
        Omit<
            ApiFileListDir,
            | 'playlists'
        > & {
        playlists: Required<ApiStationMediaPlaylist>[]
    }
    > | null
}
>
