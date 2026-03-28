import {useTranslate} from "~/vendor/gettext.ts";
import {filterMenu, MenuCategory, RawMenuCategory} from "~/functions/filterMenu.ts";
import {shallowRef, watch} from "vue";
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
import IconIcSettingsApplication from "~icons/ic/baseline-settings-applications";
import IconBiBroadcast from "~icons/bi/broadcast";
import {useUserAllowedForStation} from "~/functions/useUserallowedForStation.ts";

export function useStationsMenu() {
    const {$gettext} = useTranslate();

    const station = useStationData();
    const {userAllowedForStation} = useUserAllowedForStation();

    const fullMenu: RawMenuCategory[] = [
        {
            key: 'profile',
            label: $gettext('Overview'),
            icon: () => IconIcImage,
            url: {
                name: 'stations:index'
            }
        },
        {
            key: 'edit_profile',
            label: $gettext('Edit Station Settings'),
            icon: () => IconIcSettingsApplication,
            url: {
                name: 'stations:settings:index'
            },
            visible: () => userAllowedForStation(StationPermissions.Profile)
        },
        {
            key: 'public_page',
            label: $gettext('Public Pages'),
            icon: () => IconIcPublic,
            items: [
                {
                    key: 'branding',
                    label: $gettext('Branding'),
                    url: {
                        name: 'stations:branding'
                    },
                    visible: () => userAllowedForStation(StationPermissions.Profile)
                },
                {
                    key: 'public_player',
                    label: $gettext('Public Player'),
                    url: station.value.publicPageUrl,
                    external: true,
                    visible: () => station.value.enablePublicPages,
                },
                {
                    key: 'public_on_demand',
                    label: $gettext('On-Demand Media'),
                    url: station.value.onDemandUrl,
                    external: true,
                    visible: () => station.value.enablePublicPages && station.value.enableOnDemand,
                },
                {
                    key: 'public_podcasts',
                    label: $gettext('Podcasts'),
                    url: station.value.publicPodcastsUrl,
                    external: true,
                    visible: () => station.value.enablePublicPages,
                },
                {
                    key: 'public_schedule',
                    label: $gettext('Schedule'),
                    url: station.value.publicScheduleUrl,
                    external: true,
                    visible: () => station.value.enablePublicPages,
                },
            ]
        },
        {
            key: 'media',
            label: $gettext('Media'),
            icon: () => IconIcLibraryMusic,
            visible: () => station.value.features.media,
            items: [
                {
                    key: 'music_files',
                    label: $gettext('Music Files'),
                    url: {
                        name: 'stations:files:index'
                    },
                    visible: () => userAllowedForStation(StationPermissions.Media)
                },
                {
                    key: 'duplicate_songs',
                    label: $gettext('Duplicate Songs'),
                    url: {
                        name: 'stations:files:index',
                        params: {
                            path: 'special:duplicates'
                        }
                    },
                    visible: () => userAllowedForStation(StationPermissions.Media)
                },
                {
                    key: 'unprocessable',
                    label: $gettext('Unprocessable Files'),
                    url: {
                        name: 'stations:files:index',
                        params: {
                            path: 'special:unprocessable'
                        }
                    },
                    visible: () => userAllowedForStation(StationPermissions.Media)
                },
                {
                    key: 'unassigned',
                    label: $gettext('Unassigned Files'),
                    url: {
                        name: 'stations:files:index',
                        params: {
                            path: 'special:unassigned'
                        }
                    },
                    visible: () => userAllowedForStation(StationPermissions.Media)
                },
                {
                    key: 'ondemand',
                    label: $gettext('On-Demand Media'),
                    url: station.value.onDemandUrl,
                    external: true,
                    visible: () => station.value.enableOnDemand,
                },
                {
                    key: 'sftp_users',
                    label: $gettext('SFTP Users'),
                    url: {
                        name: 'stations:sftp_users:index'
                    },
                    visible: () => userAllowedForStation(StationPermissions.Media)
                        && station.value.features.sftp,
                },
                {
                    key: 'bulk_media',
                    label: $gettext('Bulk Media Import/Export'),
                    url: {
                        name: 'stations:bulk-media'
                    },
                    visible: () => userAllowedForStation(StationPermissions.Media)
                }
            ]
        },
        {
            key: 'playlists',
            label: $gettext('Playlists'),
            icon: () => IconIcQueueMusic,
            url: {
                name: 'stations:playlists:index'
            },
            visible: () => userAllowedForStation(StationPermissions.Media)
                && station.value.features.media,
        },
        {
            key: 'podcasts',
            label: $gettext('Podcasts'),
            icon: () => IconIcPodcasts,
            url: {
                name: 'stations:podcasts:index'
            },
            visible: () => userAllowedForStation(StationPermissions.Podcasts)
                && station.value.features.podcasts,
        },
        {
            key: 'streaming',
            label: $gettext('Live Streaming'),
            icon: () => IconIcMic,
            visible: () => userAllowedForStation(StationPermissions.Streamers)
                && station.value.features.streamers,
            items: [
                {
                    key: 'streamers',
                    label: $gettext('Streamer/DJ Accounts'),
                    url: {
                        name: 'stations:streamers:index',
                    },
                    visible: () => userAllowedForStation(StationPermissions.Streamers)
                },
                {
                    key: 'webdj',
                    label: $gettext('Web DJ'),
                    url: station.value?.webDjUrl,
                    external: true,
                    visible: () => station.value.enablePublicPages
                }
            ]
        },
        {
            key: 'webhooks',
            label: $gettext('Web Hooks'),
            icon: () => IconIcCode,
            url: {
                name: 'stations:webhooks:index'
            },
            visible: () => userAllowedForStation(StationPermissions.WebHooks)
                && station.value.features.webhooks,
        },
        {
            key: 'reports',
            label: $gettext('Reports'),
            icon: () => IconIcInsertChart,
            visible: () => userAllowedForStation(StationPermissions.Reports),
            items: [
                {
                    key: 'reports_overview',
                    label: $gettext('Station Statistics'),
                    url: {
                        name: 'stations:reports:overview',
                    }
                },
                {
                    key: 'reports_listeners',
                    label: $gettext('Listeners'),
                    url: {
                        name: 'stations:reports:listeners'
                    }
                },
                {
                    key: 'reports_requests',
                    label: $gettext('Song Requests'),
                    url: {
                        name: 'stations:reports:requests'
                    },
                    visible: () => userAllowedForStation(StationPermissions.Broadcasting)
                        && station.value.enableRequests
                },
                {
                    key: 'reports_timeline',
                    label: $gettext('Song Playback Timeline'),
                    url: {
                        name: 'stations:reports:timeline'
                    }
                },
                {
                    key: 'reports_soundexchange',
                    label: $gettext('SoundExchange Royalties'),
                    url: {
                        name: 'stations:reports:soundexchange'
                    }
                }
            ]
        },
        {
            key: 'broadcasting',
            label: $gettext('Broadcasting'),
            icon: () => IconBiBroadcast,
            items: [
                {
                    key: 'mounts',
                    label: $gettext('Mount Points'),
                    url: {
                        name: 'stations:mounts:index',
                    },
                    visible: () => userAllowedForStation(StationPermissions.MountPoints)
                        && station.value.features.mountPoints
                },
                {
                    key: 'hls_streams',
                    label: $gettext('HLS Streams'),
                    url: {
                        name: 'stations:hls_streams:index',
                    },
                    visible: () => userAllowedForStation(StationPermissions.MountPoints)
                        && station.value.features.hlsStreams
                },
                {
                    key: 'remotes',
                    label: $gettext('Remote Relays'),
                    url: {
                        name: 'stations:remotes:index',
                    },
                    visible: () => userAllowedForStation(StationPermissions.RemoteRelays)
                        && station.value.features.remoteRelays
                },
                {
                    key: 'fallback',
                    label: $gettext('Custom Fallback File'),
                    url: {
                        name: 'stations:fallback'
                    },
                    visible: () => userAllowedForStation(StationPermissions.Broadcasting)
                        && station.value.features.media
                },
                {
                    key: 'ls_config',
                    label: $gettext('Edit Liquidsoap Configuration'),
                    url: {
                        name: 'stations:util:ls_config'
                    },
                    visible: () => userAllowedForStation(StationPermissions.Broadcasting)
                        && station.value.features.customLiquidsoapConfig
                },
                {
                    key: 'stereo_tool',
                    label: $gettext('Upload Stereo Tool Configuration'),
                    url: {
                        name: 'stations:stereo_tool_config'
                    },
                    visible: () => userAllowedForStation(StationPermissions.Broadcasting)
                        && station.value.features.media
                },
                {
                    key: 'queue',
                    label: $gettext('Upcoming Song Queue'),
                    url: {
                        name: 'stations:queue:index',
                    },
                    visible: () => userAllowedForStation(StationPermissions.Broadcasting)
                        && station.value.features.autoDjQueue
                },
                {
                    key: 'restart',
                    label: $gettext('Restart Broadcasting'),
                    url: {
                        name: 'stations:restart:index',
                    },
                    visible: () => userAllowedForStation(StationPermissions.Broadcasting)
                }
            ]
        },
        {
            key: 'logs',
            label: $gettext('Logs'),
            icon: () => IconIcAssignment,
            url: {
                name: 'stations:logs'
            },
            visible: () => userAllowedForStation(StationPermissions.Logs)
        }
    ];

    const menu = shallowRef<MenuCategory[]>([]);

    watch(
        station,
        () => {
            menu.value = filterMenu(fullMenu);
        },
        {
            immediate: true
        }
    );

    return menu;
}
