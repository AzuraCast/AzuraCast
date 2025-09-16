import {
    ApiFileList,
    ApiFileListDir,
    ApiStationMedia,
    ApiStationMediaPlaylist,
    ApiStationsVueFilesProps,
    CustomField
} from "~/entities/ApiInterfaces.ts";

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
