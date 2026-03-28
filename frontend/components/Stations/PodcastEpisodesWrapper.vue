<template>
    <loading :loading="isLoading" lazy>
        <podcast-episodes v-if="podcast" :podcast="podcast"/>
    </loading>
</template>

<script setup lang="ts">
import {computed} from "vue";
import {ApiPodcast} from "~/entities/ApiInterfaces.ts";
import {QueryKeys, queryKeyWithStation} from "~/entities/Queries.ts";
import {useAxios} from "~/vendor/axios.ts";
import {useQuery} from "@tanstack/vue-query";
import {useRoute} from "vue-router";
import Loading from "~/components/Common/Loading.vue";
import PodcastEpisodes from "~/components/Stations/PodcastEpisodes.vue";
import {useApiRouter} from "~/functions/useApiRouter.ts";

const {params} = useRoute();
const podcastId = computed(() => params.podcast_id as string);

const {getStationApiUrl} = useApiRouter();
const podcastUrl = getStationApiUrl(computed(() => `/podcast/${podcastId.value}`));

const {axios} = useAxios();

type Row = Required<ApiPodcast>

const {data: podcast, isLoading} = useQuery<Row>({
    queryKey: queryKeyWithStation(
        [
            QueryKeys.StationPodcasts,
            podcastId
        ]
    ),
    queryFn: async ({signal}) => {
        const {data} = await axios.get<Row>(podcastUrl.value, {signal});
        return data;
    }
});
</script>
