import {getStationApiUrl} from "~/router.ts";
import populateComponentRemotely from "~/functions/populateComponentRemotely.ts";
import {RouteRecordRaw} from "vue-router";
import {useAxios} from "~/vendor/axios.ts";

export default function useStationsRoutes(): RouteRecordRaw[] {
    return [
        {
            path: '/station/:station_id',
            component: () => import('~/components/Stations/StationsLayout.vue'),
            children: [
                {
                    path: '',
                    component: () => import('~/components/Stations/Profile.vue'),
                    name: 'stations:index'
                },
                {
                    path: 'branding',
                    component: () => import('~/components/Stations/Branding.vue'),
                    name: 'stations:branding'
                },
                {
                    path: 'bulk-media',
                    component: () => import('~/components/Stations/BulkMedia.vue'),
                    name: 'stations:bulk-media'
                },
                {
                    path: 'fallback',
                    component: () => import('~/components/Stations/Fallback.vue'),
                    name: 'stations:fallback'
                },
                {
                    path: 'files/:path?',
                    component: () => import('~/components/Stations/Media.vue'),
                    name: 'stations:files:index',
                    ...populateComponentRemotely('/vue/files')
                },
                {
                    path: 'hls_streams',
                    component: () => import('~/components/Stations/HlsStreams.vue'),
                    name: 'stations:hls_streams:index'
                },
                {
                    path: 'ls_config',
                    component: () => import('~/components/Stations/LiquidsoapConfig.vue'),
                    name: 'stations:util:ls_config'
                },
                {
                    path: 'stereo_tool_config',
                    component: () => import('~/components/Stations/StereoToolConfig.vue'),
                    name: 'stations:stereo_tool_config',
                },
                {
                    path: 'logs',
                    component: () => import('~/components/Stations/Logs.vue'),
                    name: 'stations:logs'
                },
                {
                    path: 'playlists',
                    component: () => import('~/components/Stations/Playlists.vue'),
                    name: 'stations:playlists:index'
                },
                {
                    path: 'podcasts',
                    component: () => import('~/components/Stations/Podcasts.vue'),
                    name: 'stations:podcasts:index',
                    ...populateComponentRemotely('/vue/podcasts')
                },
                {
                    path: 'podcast/:podcast_id',
                    component: () => import('~/components/Stations/PodcastEpisodes.vue'),
                    name: 'stations:podcast:episodes',
                    beforeEnter: async (to, _, next) => {
                        const podcastId = to.params.podcast_id as string;
                        const apiUrl = getStationApiUrl(`/podcast/${podcastId}`);
                        const {axios} = useAxios();
                        to.meta.state = {
                            podcast: await axios.get(apiUrl.value).then(r => r.data)
                        };
                        next();
                    },
                    props: (to) => {
                        return to.meta.state;
                    }
                },
                {
                    path: 'profile',
                    name: 'stations:profile:index',
                    redirect: {
                        name: 'stations:index'
                    }
                },
                {
                    path: 'mounts',
                    component: () => import('~/components/Stations/Mounts.vue'),
                    name: 'stations:mounts:index',
                },
                {
                    path: 'profile/edit',
                    component: () => import('~/components/Stations/ProfileEdit.vue'),
                    name: 'stations:profile:edit',
                    ...populateComponentRemotely('/vue/profile/edit')
                },
                {
                    path: 'queue',
                    component: () => import('~/components/Stations/Queue.vue'),
                    name: 'stations:queue:index'
                },
                {
                    path: 'remotes',
                    component: () => import('~/components/Stations/Remotes.vue'),
                    name: 'stations:remotes:index'
                },
                {
                    path: 'reports/overview',
                    component: () => import('~/components/Stations/Reports/Overview.vue'),
                    name: 'stations:reports:overview',
                },
                {
                    path: 'reports/timeline',
                    component: () => import('~/components/Stations/Reports/Timeline.vue'),
                    name: 'stations:reports:timeline'
                },
                {
                    path: 'reports/listeners',
                    component: () => import('~/components/Stations/Reports/Listeners.vue'),
                    name: 'stations:reports:listeners',
                },
                {
                    path: 'reports/soundexchange',
                    component: () => import('~/components/Stations/Reports/SoundExchange.vue'),
                    name: 'stations:reports:soundexchange',
                },
                {
                    path: 'reports/requests',
                    component: () => import('~/components/Stations/Reports/Requests.vue'),
                    name: 'stations:reports:requests'
                },
                {
                    path: 'restart',
                    component: () => import('~/components/Stations/Restart.vue'),
                    name: 'stations:restart:index'
                },
                {
                    path: 'sftp_users',
                    component: () => import('~/components/Stations/SftpUsers.vue'),
                    name: 'stations:sftp_users:index',
                    ...populateComponentRemotely('/vue/sftp_users')
                },
                {
                    path: 'streamers',
                    component: () => import('~/components/Stations/Streamers.vue'),
                    name: 'stations:streamers:index',
                    ...populateComponentRemotely('/vue/streamers')
                },
                {
                    path: 'webhooks',
                    component: () => import('~/components/Stations/Webhooks.vue'),
                    name: 'stations:webhooks:index'
                }
            ]
        }
    ];
}
