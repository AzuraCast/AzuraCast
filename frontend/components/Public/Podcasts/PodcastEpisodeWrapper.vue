<template>
    <div class="full-height-scrollable">
        <loading
            :loading="podcastLoading || episodeLoading"
            lazy
        >
            <podcast-episode
                v-if="podcast && episode"
                :podcast="podcast"
                :episode="episode"
            />
        </loading>
    </div>
</template>

<script setup lang="ts">
import { useQuery } from "@tanstack/vue-query";
import { computed } from "vue";
import { useRoute } from "vue-router";
import Loading from "~/components/Common/Loading.vue";
import PodcastEpisode from "~/components/Public/Podcasts/PodcastEpisode.vue";
import { usePodcastGlobals } from "~/components/Public/Podcasts/usePodcastGlobals.ts";
import { usePodcastQuery } from "~/components/Public/Podcasts/usePodcastQuery.ts";
import { ApiPodcastEpisode } from "~/entities/ApiInterfaces.ts";
import { QueryKeys } from "~/entities/Queries.ts";
import { useApiRouter } from "~/functions/useApiRouter.ts";
import { useAxios } from "~/vendor/axios.ts";

const { stationId } = usePodcastGlobals();

const { data: podcast, isLoading: podcastLoading } = usePodcastQuery();

const { params } = useRoute();

const { getStationApiUrl } = useApiRouter();
const episodeUrl = getStationApiUrl(
    computed(() => {
        const podcastId = params.podcast_id as string;
        const episodeId = params.episode_id as string;

        return `/public/podcast/${podcastId}/episode/${episodeId}`;
    }),
    stationId,
);

const { axios } = useAxios();

export type PodcastEpisodeRow = Required<ApiPodcastEpisode>;

const { data: episode, isLoading: episodeLoading } =
    useQuery<PodcastEpisodeRow>({
        queryKey: [
            QueryKeys.PublicPodcasts,
            { station: stationId },
            params.podcast_id,
            "episodes",
            params.episode_id,
        ],
        queryFn: async ({ signal }) => {
            const { data } = await axios.get<PodcastEpisodeRow>(
                episodeUrl.value,
                { signal },
            );
            return data;
        },
        staleTime: 5 * 60 * 1000,
    });
</script>
