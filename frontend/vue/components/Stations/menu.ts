import {useTranslate} from "~/vendor/gettext.ts";
import filterMenu from "~/functions/filterMenu.ts";
import {StationPermission, userAllowedForStation} from "~/acl.ts";
import {useAzuraCast} from "~/vendor/azuracast.ts";

export function useStationsMenu(): array {
    const {$gettext} = useTranslate();

    const {enableAdvancedFeatures, sidebarProps} = useAzuraCast();
    const stationProps = sidebarProps.station;

    // Reuse this variable to avoid multiple calls.
    const userCanManageMedia = userAllowedForStation(StationPermission.Media);

    const menu = [
        {
            key: 'profile',
            label: $gettext('Profile'),
            icon: 'image',
            items: [
                {
                    key: 'view_profile',
                    label: $gettext('View Profile'),
                    url: {
                        name: 'stations:index'
                    }
                },
                {
                    key: 'edit_profile',
                    label: $gettext('Edit Profile'),
                    url: {
                        name: 'stations:profile:edit'
                    },
                    visible: userAllowedForStation(StationPermission.Profile)
                },
                {
                    key: 'branding',
                    label: $gettext('Branding'),
                    url: {
                        name: 'stations:branding'
                    },
                    visible: userAllowedForStation(StationPermission.Profile)
                }
            ]
        },
        {
            key: 'public_page',
            label: $gettext('Public Page'),
            icon: 'public',
            url: stationProps.publicPageUrl,
            external: true,
            visible: stationProps.enablePublicPages,
        },
        {
            key: 'media',
            label: $gettext('Media'),
            icon: 'library_music',
            visible: stationProps.features.media,
            items: [
                {
                    key: 'music_files',
                    label: $gettext('Music Files'),
                    url: {
                        name: 'stations:files:index'
                    },
                    visible: userCanManageMedia
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
                    visible: userCanManageMedia
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
                    visible: userCanManageMedia
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
                    visible: userCanManageMedia
                },
                {
                    key: 'ondemand',
                    label: $gettext('On-Demand Media'),
                    url: stationProps.onDemandUrl,
                    external: true,
                    visible: stationProps.enableOnDemand,
                },
                {
                    key: 'sftp_users',
                    label: $gettext('SFTP Users'),
                    url: {
                        name: 'stations:sftp_users:index'
                    },
                    visible: userCanManageMedia && stationProps.features.sftp,
                },
                {
                    key: 'bulk_media',
                    label: $gettext('Bulk Media Import/Export'),
                    url: {
                        name: 'stations:bulk-media'
                    },
                    visible: userCanManageMedia
                }
            ]
        },
        {
            key: 'playlists',
            label: $gettext('Playlists'),
            icon: 'queue_music',
            url: {
                name: 'stations:playlists:index'
            },
            visible: userCanManageMedia && stationProps.features.media,
        },
        {
            key: 'podcasts',
            label: $gettext('Podcasts'),
            icon: 'cast',
            url: {
                name: 'stations:podcasts:index'
            },
            visible: userAllowedForStation(StationPermission.Podcasts) && stationProps.features.podcasts,
        },
        {
            key: 'streaming',
            label: $gettext('Live Streaming'),
            icon: 'mic',
            visible: userAllowedForStation(StationPermission.Streamers) && stationProps.features.streamers,
            items: [
                {
                    key: 'streamers',
                    label: $gettext('Streamer/DJ Accounts'),
                    url: {
                        name: 'stations:streamers:index',
                    },
                    visible: userAllowedForStation(StationPermission.Streamers)
                },
                {
                    key: 'webdj',
                    label: $gettext('Web DJ'),
                    url: stationProps.webDjUrl,
                    external: true,
                    visible: stationProps.enablePublicPages
                }
            ]
        },
        {
            key: 'webhooks',
            label: $gettext('Web Hooks'),
            icon: 'code',
            url: {
                name: 'stations:webhooks:index'
            },
            visible: userAllowedForStation(StationPermission.WebHooks) && stationProps.features.webhooks,
        },
        {
            key: 'reports',
            label: $gettext('Reports'),
            icon: 'assignment',
            visible: userAllowedForStation(StationPermission.Reports),
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
                    visible: userAllowedForStation(StationPermission.Broadcasting) && stationProps.enableRequests
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
            icon: 'wifi_tethering',
            items: [
                {
                    key: 'mounts',
                    label: $gettext('Mount Points'),
                    url: {
                        name: 'stations:mounts:index',
                    },
                    visible: userAllowedForStation(StationPermission.MountPoints) && stationProps.features.mountPoints
                },
                {
                    key: 'hls_streams',
                    label: $gettext('HLS Streams'),
                    url: {
                        name: 'stations:hls_streams:index',
                    },
                    visible: userAllowedForStation(StationPermission.MountPoints) && stationProps.features.hlsStreams
                },
                {
                    key: 'remotes',
                    label: $gettext('Remote Relays'),
                    url: {
                        name: 'stations:remotes:index',
                    },
                    visible: userAllowedForStation(StationPermission.RemoteRelays) && stationProps.features.remoteRelays
                },
                {
                    key: 'fallback',
                    label: $gettext('Custom Fallback File'),
                    url: {
                        name: 'stations:fallback'
                    },
                    visible: userAllowedForStation(StationPermission.Broadcasting) && stationProps.features.media
                },
                {
                    key: 'ls_config',
                    label: $gettext('Edit Liquidsoap Configuration'),
                    url: {
                        name: 'stations:util:ls_config'
                    },
                    visible: userAllowedForStation(StationPermission.Broadcasting)
                        && stationProps.features.customLiquidsoapConfig
                },
                {
                    key: 'stereo_tool',
                    label: $gettext('Upload Stereo Tool Configuration'),
                    url: {
                        name: 'stations:stereo_tool_config'
                    },
                    visible: stationProps.features.media && enableAdvancedFeatures
                        && userAllowedForStation(StationPermission.Broadcasting)
                },
                {
                    key: 'queue',
                    label: $gettext('Upcoming Song Queue'),
                    url: {
                        name: 'stations:queue:index',
                    },
                    visible: userAllowedForStation(StationPermission.Broadcasting) && stationProps.features.autoDjQueue
                },
                {
                    key: 'restart',
                    label: $gettext('Restart Broadcasting'),
                    url: {
                        name: 'stations:restart:index',
                    },
                    visible: userAllowedForStation(StationPermission.Broadcasting)
                }
            ]
        },
        {
            key: 'logs',
            label: $gettext('Logs'),
            icon: 'web_stories',
            url: {
                name: 'stations:logs'
            },
            visible: userAllowedForStation(StationPermission.Logs)
        }
    ];

    return filterMenu(menu);
}
