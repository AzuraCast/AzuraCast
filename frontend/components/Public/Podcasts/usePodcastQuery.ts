import { useQuery } from "@tanstack/vue-query";
import { computed } from "vue";
import { useRoute } from "vue-router";
import { usePodcastGlobals } from "~/components/Public/Podcasts/usePodcastGlobals.ts";
import { ApiPodcast } from "~/entities/ApiInterfaces.ts";
import { QueryKeys } from "~/entities/Queries.ts";
import { useApiRouter } from "~/functions/useApiRouter.ts";
import { useAxios } from "~/vendor/axios.ts";

export type ApiPodcastRow = Required<ApiPodcast>;

export const usePodcastQuery = () => {
    const { stationId } = usePodcastGlobals();
    const { axios } = useAxios();
    const { params } = useRoute();

    const { getStationApiUrl } = useApiRouter();
    const podcastUrl = getStationApiUrl(
        computed(() => {
            const podcastId = params.podcast_id as string;
            return `/public/podcast/${podcastId}`;
        }),
        stationId,
    );

    return useQuery<ApiPodcastRow>({
        queryKey: [
            QueryKeys.PublicPodcasts,
            { station: stationId },
            params.podcast_id,
        ],
        queryFn: async ({ signal }) => {
            const { data } = await axios.get(podcastUrl.value, { signal });
            return data;
        },
        staleTime: 5 * 60 * 1000,
    });
};
