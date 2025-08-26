<template>
    <div class="full-height-scrollable">
        <div class="card-body">
            <loading
                :loading="podcastLoading || episodeLoading"
                lazy
            >
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item">
                            <router-link :to="{name: 'public:podcasts'}">
                                {{ $gettext('Podcasts') }}
                            </router-link>
                        </li>
                        <li class="breadcrumb-item">
                            <router-link :to="{name: 'public:podcast', params: {podcast_id: podcast.id}}">
                                {{ podcast.title }}
                            </router-link>
                        </li>
                        <li class="breadcrumb-item">
                            {{ episode.title }}
                        </li>
                    </ol>
                </nav>
            </loading>
        </div>

        <div
            class="card-body alert alert-secondary"
            aria-live="polite"
        >
            <loading
                :loading="podcastLoading"
                lazy
            >
                <podcast-common :podcast="podcast" />
            </loading>
        </div>

        <div class="card-body">
            <loading
                :loading="episodeLoading"
                lazy
            >
                <div class="d-flex">
                    <div class="flex-shrink-0 pe-3">
                        <play-button
                            icon-class="xl"
                            :stream="{
                                title: episode.title,
                                url: episode.links.download
                            }"
                        />
                    </div>
                    <div class="flex-fill">
                        <h4 class="card-title mb-1">
                            {{ episode.title }}
                        </h4>

                        <div class="badges my-2">
                            <span
                                v-if="episode.publish_at"
                                class="badge text-bg-secondary"
                            >
                                {{ formatTimestampAsDateTime(episode.publish_at) }}
                            </span>
                            <span
                                v-else
                                class="badge text-bg-secondary"
                            >
                                {{ formatTimestampAsDateTime(episode.created_at) }}
                            </span>
                            <span
                                v-if="episode.explicit"
                                class="badge text-bg-danger"
                            >
                                {{ $gettext('Explicit') }}
                            </span>
                        </div>

                        <p class="card-text">
                            {{ episode.description }}
                        </p>
                    </div>
                    <div class="flex-shrink-0 ps-3">
                        <album-art
                            :src="episode.art"
                            :width="96"
                        />
                    </div>
                </div>
            </loading>
        </div>
    </div>
</template>

<script setup lang="ts">
import Loading from "~/components/Common/Loading.vue";
import {useRoute} from "vue-router";
import {getStationApiUrl} from "~/router.ts";
import {useAxios} from "~/vendor/axios.ts";
import AlbumArt from "~/components/Common/AlbumArt.vue";
import PlayButton from "~/components/Common/PlayButton.vue";
import useStationDateTimeFormatter from "~/functions/useStationDateTimeFormatter.ts";
import PodcastCommon from "~/components/Public/Podcasts/PodcastCommon.vue";
import {usePodcastGlobals} from "~/components/Public/Podcasts/usePodcastGlobals.ts";
import {computed} from "vue";
import {usePodcastQuery} from "~/components/Public/Podcasts/usePodcastQuery.ts";
import {useQuery} from "@tanstack/vue-query";
import {QueryKeys} from "~/entities/Queries.ts";

const {stationId, stationTz} = usePodcastGlobals();

const {data: podcast, isLoading: podcastLoading} = usePodcastQuery();

const {params} = useRoute();

const episodeUrl = getStationApiUrl(computed(() => {
    const podcastId = params.podcast_id as string;
    const episodeId = params.episode_id as string;

    return `/public/podcast/${podcastId}/episode/${episodeId}`;
}), stationId);

const {axios} = useAxios();

const {data: episode, isLoading: episodeLoading} = useQuery({
    queryKey: [
        QueryKeys.PublicPodcasts,
        {station: stationId},
        params.podcast_id,
        'episodes',
        params.episode_id
    ],
    queryFn: async ({signal}) => {
        const {data} = await axios.get(episodeUrl.value, {signal});
        return data;
    },
    staleTime: 5 * 60 * 1000
});

const {formatTimestampAsDateTime} = useStationDateTimeFormatter(stationTz);
</script>
