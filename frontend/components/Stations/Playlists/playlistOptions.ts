import { playlistSourceIcons } from "~/components/Stations/Common/playlistSourceIcons.ts";
import {
    PlaylistOrders,
    PlaylistSources,
    PlaylistTypes,
} from "~/entities/ApiInterfaces.ts";
import { useTranslate } from "~/vendor/gettext";

export function usePlaylistOptions() {
    const { $gettext } = useTranslate();

    const sourceOptions = [
        {
            value: PlaylistSources.Songs,
            icon: () => playlistSourceIcons[PlaylistSources.Songs],
            text: $gettext("Song-Based"),
            description: $gettext(
                "A playlist containing media files hosted on this server.",
            ),
        },
        {
            value: PlaylistSources.Playlists,
            icon: () => playlistSourceIcons[PlaylistSources.Playlists],
            text: $gettext("Playlist Group"),
            description: $gettext("A playlist containing other playlists."),
        },
        {
            value: PlaylistSources.RemoteUrl,
            icon: () => playlistSourceIcons[PlaylistSources.RemoteUrl],
            text: $gettext("Remote URL"),
            description: $gettext(
                "A playlist that instructs the station to play from a remote URL.",
            ),
        },
        {
            value: PlaylistSources.Requests,
            icon: () => playlistSourceIcons[PlaylistSources.Requests],
            text: $gettext("Request Queue"),
            description: $gettext(
                "A playlist that plays songs requested by listeners.",
            ),
        },
    ];

    const typeOptions = [
        {
            value: PlaylistTypes.Standard,
            text: $gettext("General Rotation"),
            description: $gettext(
                "Standard playlist, shuffles with other standard playlists based on weight.",
            ),
        },
        {
            value: PlaylistTypes.OncePerXSongs,
            text: $gettext("Once per x Songs"),
            description: $gettext("Play once every $x songs."),
        },
        {
            value: PlaylistTypes.OncePerXMinutes,
            text: $gettext("Once per x Minutes"),
            description: $gettext("Play once every $x minutes."),
        },
        {
            value: PlaylistTypes.OncePerHour,
            text: $gettext("Once per Hour"),
            description: $gettext(
                "Play once per hour at the specified minute.",
            ),
        },
        {
            value: PlaylistTypes.Advanced,
            text: $gettext("Advanced"),
            description: $gettext(
                "Manually define how this playlist is used in Liquidsoap configuration.",
            ),
        },
    ];

    const orderOptions = [
        {
            value: PlaylistOrders.Shuffle,
            text: $gettext("Shuffled"),
            description: $gettext(
                "The full playlist is shuffled and then played through in the shuffled order.",
            ),
        },
        {
            value: PlaylistOrders.Random,
            text: $gettext("Random"),
            description: $gettext(
                "A completely random track is picked for playback every time the queue is populated.",
            ),
        },
        {
            value: PlaylistOrders.Sequential,
            text: $gettext("Sequential"),
            description: $gettext(
                "The order of the playlist is manually specified and followed by the AutoDJ.",
            ),
        },
    ];

    return { sourceOptions, typeOptions, orderOptions };
}
