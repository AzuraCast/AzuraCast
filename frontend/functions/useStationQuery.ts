import {useQuery} from "@tanstack/vue-query";
import {VueStationGlobals} from "~/entities/ApiInterfaces.ts";
import {computed, ComputedRef} from "vue";
import {useAxios} from "~/vendor/axios.ts";
import {QueryKeys} from "~/entities/Queries.ts";
import {useRoute} from "vue-router";
import {getApiUrl} from "~/router.ts";

export const useStationId = (): ComputedRef<number | null> => {
    const {params} = useRoute();
    return computed(() => Number(params.station_id) ?? null);
}

export const useStationQuery = () => {
    const {axios} = useAxios();
    const stationId = useStationId();

    return useQuery<VueStationGlobals>({
        queryKey: [QueryKeys.StationGlobals, stationId],
        queryFn: async ({signal}) => {
            const dashboardUrl = getApiUrl(`'/station/${stationId.value}/dashboard`);
            const {data} = await axios.get<VueStationGlobals>(dashboardUrl.value, {signal});
            return data;
        }
    });
}
