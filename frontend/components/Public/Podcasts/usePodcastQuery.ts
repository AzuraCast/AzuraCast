import {useQuery} from "@tanstack/vue-query";
import {ApiPodcast} from "~/entities/ApiInterfaces.ts";
import {QueryKeys} from "~/entities/Queries.ts";
import {usePodcastGlobals} from "~/components/Public/Podcasts/usePodcastGlobals.ts";
import {getStationApiUrl} from "~/router.ts";
import {computed} from "vue";
import {useRoute} from "vue-router";
import {useAxios} from "~/vendor/axios.ts";

export const usePodcastQuery = () => {
    const {stationId} = usePodcastGlobals();
    const {axios} = useAxios();
    const {params} = useRoute();

    const podcastUrl = getStationApiUrl(computed(() => {
        const podcastId = params.podcast_id as string;
        return `/public/podcast/${podcastId}`;
    }), stationId);

    return useQuery<ApiPodcast>({
        queryKey: [
            QueryKeys.PublicPodcasts,
            {station: stationId},
            params.podcast_id,
        ],
        queryFn: async () => {
            const {data} = await axios.get(podcastUrl.value);
            return data;
        },
        staleTime: 5 * 60 * 1000
    });
};
