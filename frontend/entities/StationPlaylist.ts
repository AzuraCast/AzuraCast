import {
    ApiStationSchedule,
    HasLinks,
    StationPlaylist,
    StationPlaylistGroup,
} from "~/entities/ApiInterfaces.ts";

// Playlist as returned by the list API including runtime-computed fields
// not present in the generated StationPlaylist type.
export type StationPlaylistEnriched = Required<
    Omit<StationPlaylist, 'podcasts' | 'schedule_items' | 'playlists'>
> & Required<HasLinks> & {
    num_songs: number,
    playlists: StationPlaylistGroupMemberEnriched[],
    schedule_items: ApiStationSchedule[],
    links: {
        self: string,
        members?: string,
    },
};

// Enriched member used in PlaylistGroupingTab after buildTree() hydration.
// Extends the raw API member with runtime-computed fields for UI rendering.
export type StationPlaylistGroupMemberEnriched = Required<StationPlaylistGroup> & {
    source: string,
    num_songs: number,
    playlists: StationPlaylistGroup[],
};

export type PlaylistBreadcrumb = {
    id: number,
    name: string,
};
