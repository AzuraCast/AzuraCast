import {useTranslate} from "~/vendor/gettext.ts";
import filterMenu, {MenuCategory, ReactiveMenu} from "~/functions/filterMenu.ts";
import {userAllowedForStation} from "~/acl.ts";
import {computed, markRaw} from "vue";
import {reactiveComputed} from "@vueuse/core";
import {StationPermissions} from "~/entities/ApiInterfaces.ts";
import {useStationData} from "~/functions/useStationQuery.ts";
import IconIcCode from "~icons/ic/baseline-code";
import IconIcImage from "~icons/ic/baseline-image";
import IconIcLibraryMusic from "~icons/ic/baseline-library-music";
import IconIcAssignment from "~icons/ic/baseline-assignment";
import IconIcMic from "~icons/ic/baseline-mic";
import IconIcQueueMusic from "~icons/ic/baseline-queue-music";
import IconIcPodcasts from "~icons/ic/baseline-podcasts";
import IconIcPublic from "~icons/ic/baseline-public";
import IconIcInsertChart from "~icons/ic/baseline-insert-chart";
import IconBiBroadcast from "~icons/bi/broadcast";

export function useStationsMenu(): ReactiveMenu {
    const {$gettext} = useTranslate();

    const station = useStationData();

    // Reuse this variable to avoid multiple calls.
    const userCanManageMedia = userAllowedForStation(StationPermissions.Media);

    const menu: ReactiveMenu = reactiveComputed(
        () => {
            const profileMenu: MenuCategory = {
                key: 'profile',
                label: computed(() => $gettext('Profile')),
                icon: markRaw(IconIcImage),
                items: [
                    {
                        key: 'view_profile',
                        label: computed(() => $gettext('View Profile')),
                        url: {
                            name: 'stations:index'
                        }
                    },
                    {
                        key: 'edit_profile',
                        label: computed(() => $gettext('Edit Profile')),
                        url: {
                            name: 'stations:profile:edit'
                        },
                        visible: userAllowedForStation(StationPermissions.Profile)
                    },
                    {
                        key: 'branding',
                        label: computed(() => $gettext('Branding')),
                        url: {
                            name: 'stations:branding'
                        },
                        visible: userAllowedForStation(StationPermissions.Profile)
                    }
                ]
            };

            const publicPageMenu: MenuCategory = {
                key: 'public_page',
                label: computed(() => $gettext('Public Page')),
                icon: markRaw(IconIcPublic),
                url: station.value.publicPageUrl,
                external: true,
                visible: station.value.enablePublicPages,
            };

            const mediaMenu: MenuCategory = {
                key: 'media',
                label: computed(() => $gettext('Media')),
                icon: markRaw(IconIcLibraryMusic),
                visible: station.value.features.media,
                items: [
                    {
                        key: 'music_files',
                        label: computed(() => $gettext('Music Files')),
                        url: {
                            name: 'stations:files:index'
                        },
                        visible: userCanManageMedia
                    },
                    {
                        key: 'duplicate_songs',
                        label: computed(() => $gettext('Duplicate Songs')),
                        url: {
                            name: 'stations:files:index',
                            params: {
                                path: 'special:duplicates'
                            }
                        },
                        visible: userCanManageMedia
                    },
                    {
                        key: 'unprocessable',
                        label: computed(() => $gettext('Unprocessable Files')),
                        url: {
                            name: 'stations:files:index',
                            params: {
                                path: 'special:unprocessable'
                            }
                        },
                        visible: userCanManageMedia
                    },
                    {
                        key: 'unassigned',
                        label: computed(() => $gettext('Unassigned Files')),
                        url: {
                            name: 'stations:files:index',
                            params: {
                                path: 'special:unassigned'
                            }
                        },
                        visible: userCanManageMedia
                    },
                    {
                        key: 'ondemand',
                        label: computed(() => $gettext('On-Demand Media')),
                        url: station.value.onDemandUrl,
                        external: true,
                        visible: station.value.enableOnDemand,
                    },
                    {
                        key: 'sftp_users',
                        label: computed(() => $gettext('SFTP Users')),
                        url: {
                            name: 'stations:sftp_users:index'
                        },
                        visible: userCanManageMedia && station.value.features.sftp,
                    },
                    {
                        key: 'bulk_media',
                        label: computed(() => $gettext('Bulk Media Import/Export')),
                        url: {
                            name: 'stations:bulk-media'
                        },
                        visible: userCanManageMedia
                    }
                ]
            };

            const playlistsMenu: MenuCategory = {
                key: 'playlists',
                label: computed(() => $gettext('Playlists')),
                icon: markRaw(IconIcQueueMusic),
                url: {
                    name: 'stations:playlists:index'
                },
                visible: userCanManageMedia && station.value.features.media,
            };

            const podcastsMenu: MenuCategory = {
                key: 'podcasts',
                label: computed(() => $gettext('Podcasts')),
                icon: markRaw(IconIcPodcasts),
                url: {
                    name: 'stations:podcasts:index'
                },
                visible: userAllowedForStation(StationPermissions.Podcasts) && station.value.features.podcasts,
            };

            const streamingMenu: MenuCategory = {
                key: 'streaming',
                label: computed(() => $gettext('Live Streaming')),
                icon: markRaw(IconIcMic),
                visible: userAllowedForStation(StationPermissions.Streamers) && station.value.features.streamers,
                items: [
                    {
                        key: 'streamers',
                        label: computed(() => $gettext('Streamer/DJ Accounts')),
                        url: {
                            name: 'stations:streamers:index',
                        },
                        visible: userAllowedForStation(StationPermissions.Streamers)
                    },
                    {
                        key: 'webdj',
                        label: computed(() => $gettext('Web DJ')),
                        url: station.value?.webDjUrl,
                        external: true,
                        visible: station.value.enablePublicPages
                    }
                ]
            };

            const webhooksMenu: MenuCategory = {
                key: 'webhooks',
                label: computed(() => $gettext('Web Hooks')),
                icon: markRaw(IconIcCode),
                url: {
                    name: 'stations:webhooks:index'
                },
                visible: userAllowedForStation(StationPermissions.WebHooks) && station.value.features.webhooks,
            };

            const reportsMenu: MenuCategory = {
                key: 'reports',
                label: computed(() => $gettext('Reports')),
                icon: markRaw(IconIcInsertChart),
                visible: userAllowedForStation(StationPermissions.Reports),
                items: [
                    {
                        key: 'reports_overview',
                        label: computed(() => $gettext('Station Statistics')),
                        url: {
                            name: 'stations:reports:overview',
                        }
                    },
                    {
                        key: 'reports_listeners',
                        label: computed(() => $gettext('Listeners')),
                        url: {
                            name: 'stations:reports:listeners'
                        }
                    },
                    {
                        key: 'reports_requests',
                        label: computed(() => $gettext('Song Requests')),
                        url: {
                            name: 'stations:reports:requests'
                        },
                        visible: userAllowedForStation(StationPermissions.Broadcasting) && station.value.enableRequests
                    },
                    {
                        key: 'reports_timeline',
                        label: computed(() => $gettext('Song Playback Timeline')),
                        url: {
                            name: 'stations:reports:timeline'
                        }
                    },
                    {
                        key: 'reports_soundexchange',
                        label: computed(() => $gettext('SoundExchange Royalties')),
                        url: {
                            name: 'stations:reports:soundexchange'
                        }
                    }
                ]
            };

            const broadcastingMenu: MenuCategory = {
                key: 'broadcasting',
                label: computed(() => $gettext('Broadcasting')),
                icon: markRaw(IconBiBroadcast),
                items: [
                    {
                        key: 'mounts',
                        label: computed(() => $gettext('Mount Points')),
                        url: {
                            name: 'stations:mounts:index',
                        },
                        visible: userAllowedForStation(StationPermissions.MountPoints) && station.value.features.mountPoints
                    },
                    {
                        key: 'hls_streams',
                        label: computed(() => $gettext('HLS Streams')),
                        url: {
                            name: 'stations:hls_streams:index',
                        },
                        visible: userAllowedForStation(StationPermissions.MountPoints) && station.value.features.hlsStreams
                    },
                    {
                        key: 'remotes',
                        label: computed(() => $gettext('Remote Relays')),
                        url: {
                            name: 'stations:remotes:index',
                        },
                        visible: userAllowedForStation(StationPermissions.RemoteRelays) && station.value.features.remoteRelays
                    },
                    {
                        key: 'fallback',
                        label: computed(() => $gettext('Custom Fallback File')),
                        url: {
                            name: 'stations:fallback'
                        },
                        visible: userAllowedForStation(StationPermissions.Broadcasting) && station.value.features.media
                    },
                    {
                        key: 'ls_config',
                        label: computed(() => $gettext('Edit Liquidsoap Configuration')),
                        url: {
                            name: 'stations:util:ls_config'
                        },
                        visible: userAllowedForStation(StationPermissions.Broadcasting)
                            && station.value.features.customLiquidsoapConfig
                    },
                    {
                        key: 'stereo_tool',
                        label: computed(() => $gettext('Upload Stereo Tool Configuration')),
                        url: {
                            name: 'stations:stereo_tool_config'
                        },
                        visible: station.value.features.media && userAllowedForStation(StationPermissions.Broadcasting)
                    },
                    {
                        key: 'queue',
                        label: computed(() => $gettext('Upcoming Song Queue')),
                        url: {
                            name: 'stations:queue:index',
                        },
                        visible: userAllowedForStation(StationPermissions.Broadcasting) && station.value.features.autoDjQueue
                    },
                    {
                        key: 'restart',
                        label: computed(() => $gettext('Restart Broadcasting')),
                        url: {
                            name: 'stations:restart:index',
                        },
                        visible: userAllowedForStation(StationPermissions.Broadcasting)
                    }
                ]
            };

            const logsMenu: MenuCategory = {
                key: 'logs',
                label: computed(() => $gettext('Logs')),
                icon: markRaw(IconIcAssignment),
                url: {
                    name: 'stations:logs'
                },
                visible: userAllowedForStation(StationPermissions.Logs)
            };

            return {
                categories: [
                    profileMenu,
                    publicPageMenu,
                    mediaMenu,
                    playlistsMenu,
                    podcastsMenu,
                    streamingMenu,
                    webhooksMenu,
                    reportsMenu,
                    broadcastingMenu,
                    logsMenu
                ]
            }
        }
    ) as unknown as ReactiveMenu;

    return filterMenu(menu);
}
