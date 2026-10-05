import { Component } from "vue";
import { PlaylistSources } from "~/entities/ApiInterfaces.ts";
import IconBiPeople from "~icons/bi/people";
import IconIcLibraryMusic from "~icons/ic/baseline-library-music";
import IconIcPublic from "~icons/ic/baseline-public";
import IconIcQueueMusic from "~icons/ic/baseline-queue-music";

export const playlistSourceIcons: Record<PlaylistSources, Component> = {
    [PlaylistSources.Songs]: IconIcLibraryMusic,
    [PlaylistSources.Playlists]: IconIcQueueMusic,
    [PlaylistSources.Requests]: IconBiPeople,
    [PlaylistSources.RemoteUrl]: IconIcPublic,
};

export const isPlaylistSource = (value: string): value is PlaylistSources => {
    return Object.values(PlaylistSources).includes(value as PlaylistSources);
};
