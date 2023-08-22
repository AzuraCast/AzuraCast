import {getStationApiUrl} from "~/router.ts";
import populateComponentRemotely from "~/functions/populateComponentRemotely.ts";

export default function useStationsRoutes() {
    return [
        {
            path: '/',
            component: () => import('~/components/Stations/Profile.vue'),
            name: 'stations:index',
            ...populateComponentRemotely(getStationApiUrl('/vue/profile'))
        },
        {
            path: '/branding',
            component: () => import('~/components/Stations/Branding.vue'),
            name: 'stations:branding'
        },
        {
            path: '/bulk-media',
            component: () => import('~/components/Stations/BulkMedia.vue'),
            name: 'stations:bulk-media'
        },
        {
            path: '/fallback',
            component: () => import('~/components/Stations/Fallback.vue'),
            name: 'stations:fallback'
        },
        {
            path: '/files/:path?',
            component: () => import('~/components/Stations/Media.vue'),
            name: 'stations:files:index',
            ...populateComponentRemotely(getStationApiUrl('/vue/files'))
        },
        {
            path: '/hls_streams',
            component: () => import('~/components/Stations/HlsStreams.vue'),
            name: 'stations:hls_streams:index'
        },
        {
            path: '/ls_config',
            component: () => import('~/components/Stations/LiquidsoapConfig.vue'),
            name: 'stations:util:ls_config'
        },
        {
            path: '/stereo_tool_config',
            component: () => import('~/components/Stations/StereoToolConfig.vue'),
            name: 'stations:stereo_tool_config',
        },
        {
            path: '/logs',
            component: () => import('~/components/Stations/Logs.vue'),
            name: 'stations:logs'
        },
        {
            path: '/playlists',
            component: () => import('~/components/Stations/Playlists.vue'),
            name: 'stations:playlists:index',
            ...populateComponentRemotely(getStationApiUrl('/vue/playlists'))
        },
        {
            path: '/podcasts',
            component: () => import('~/components/Stations/Podcasts.vue'),
            name: 'stations:podcasts:index',
            ...populateComponentRemotely(getStationApiUrl('/vue/podcasts'))
        },
        {
            path: '/profile',
            name: 'stations:profile:index',
            redirect: {
                name: 'stations:index'
            }
        },
        {
            path: '/mounts',
            component: () => import('~/components/Stations/Mounts.vue'),
            name: 'stations:mounts:index',
            ...populateComponentRemotely(getStationApiUrl('/vue/mounts'))
        },
        {
            path: '/profile/edit',
            component: () => import('~/components/Stations/ProfileEdit.vue'),
            name: 'stations:profile:edit',
            ...populateComponentRemotely(getStationApiUrl('/vue/profile/edit'))
        },
        {
            path: '/queue',
            component: () => import('~/components/Stations/Queue.vue'),
            name: 'stations:queue:index'
        },
        {
            path: '/remotes',
            component: () => import('~/components/Stations/Remotes.vue'),
            name: 'stations:remotes:index'
        },
        {
            path: '/reports/overview',
            component: () => import('~/components/Stations/Reports/Overview.vue'),
            name: 'stations:reports:overview',
            ...populateComponentRemotely(getStationApiUrl('/vue/reports/overview'))
        },
        {
            path: '/reports/timeline',
            component: () => import('~/components/Stations/Reports/Timeline.vue'),
            name: 'stations:reports:timeline'
        },
        {
            path: '/reports/listeners',
            component: () => import('~/components/Stations/Reports/Listeners.vue'),
            name: 'stations:reports:listeners',
            ...populateComponentRemotely(getStationApiUrl('/vue/reports/listeners'))
        },
        {
            path: '/reports/soundexchange',
            component: () => import('~/components/Stations/Reports/SoundExchange.vue'),
            name: 'stations:reports:soundexchange',
        },
        {
            path: '/reports/requests',
            component: () => import('~/components/Stations/Reports/Requests.vue'),
            name: 'stations:reports:requests'
        },
        {
            path: '/restart',
            component: () => import('~/components/Stations/Restart.vue'),
            name: 'stations:restart:index',
            ...populateComponentRemotely(getStationApiUrl('/vue/restart'))
        },
        {
            path: '/sftp_users',
            component: () => import('~/components/Stations/SftpUsers.vue'),
            name: 'stations:sftp_users:index',
            ...populateComponentRemotely(getStationApiUrl('/vue/sftp_users'))
        },
        {
            path: '/streamers',
            component: () => import('~/components/Stations/Streamers.vue'),
            name: 'stations:streamers:index',
            ...populateComponentRemotely(getStationApiUrl('/vue/streamers'))
        },
        {
            path: '/webhooks',
            component: () => import('~/components/Stations/Webhooks.vue'),
            name: 'stations:webhooks:index'
        }
    ];
}
