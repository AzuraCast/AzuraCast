import {RouteRecordRaw} from "vue-router";

export default function usePodcastRoutes(): RouteRecordRaw[] {
    return [
        {
            path: '/podcasts',
            component: () => import('~/components/Public/Podcasts/PodcastList.vue'),
            name: 'public:podcasts'
        },
        {
            path: '/podcast/:podcast_id',
            component: () => import('~/components/Public/Podcasts/Podcast.vue'),
            name: 'public:podcast'
        },
        {
            path: '/podcast/:podcast_id/episode/:episode_id',
            component: () => import('~/components/Public/Podcasts/PodcastEpisode.vue'),
            name: 'public:podcast:episode'
        }
    ];
}
