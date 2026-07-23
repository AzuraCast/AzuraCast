import {
    ApiStationPlaylistComputedFields,
    ApiStationPlaylistParentGroup,
    ApiStationSchedule,
    HasLinks,
    StationPlaylist,
    StationPlaylistGroup,
} from "~/entities/ApiInterfaces.ts";

export type PlaylistBreadcrumb = ApiStationPlaylistParentGroup;

// Playlist as returned by the list API including runtime-computed fields.
export type StationPlaylistEnriched = Required<
    Omit<StationPlaylist, "podcasts" | "schedule_items" | "playlists">
> &
    Required<HasLinks> &
    Required<ApiStationPlaylistComputedFields> & {
        // Tightened generated computed fields for template ergonomics.
        num_songs: number;
        playlist_groups: PlaylistBreadcrumb[];
        // Enriched relations with no generated equivalent.
        playlists: StationPlaylistGroupMemberEnriched[];
        schedule_items: ApiStationSchedule[];
        links: {
            self: string;
            members?: string;
        };
    };

// Enriched member used in PlaylistGroupingTab after buildTree() hydration.
// Extends the raw API member with runtime-computed fields for UI rendering.
export type StationPlaylistGroupMemberEnriched =
    Required<StationPlaylistGroup> & {
        source: string;
        order: string;
        num_songs: number;
        is_enabled: boolean;
        playlists: StationPlaylistGroup[];
    };
