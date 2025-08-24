import {useQuery} from "@tanstack/vue-query";
import {VueStationGlobals} from "~/entities/ApiInterfaces.ts";
import {computed, ComputedRef} from "vue";
import {useAxios} from "~/vendor/axios.ts";
import {QueryKeys} from "~/entities/Queries.ts";
import {useRoute} from "vue-router";
import {getStationApiUrl} from "~/router.ts";

export const useStationId = (): ComputedRef<number | null> => {
    const {params} = useRoute();
    return computed(() => Number(params.station_id) ?? null);
}

export const useStationQuery = () => {
    const {axios} = useAxios();
    const stationId = useStationId();

    const dashboardUrl = getStationApiUrl('/dashboard');

    return useQuery<VueStationGlobals>({
        queryKey: [QueryKeys.StationGlobals, stationId],
        queryFn: async ({signal}) => {
            console.log(dashboardUrl.value);

            const {data} = await axios.get<VueStationGlobals>(dashboardUrl.value, {signal});
            return data;
        },
        staleTime: 10 * 60 * 1000,
        placeholderData: {
            id: 0,
            name: null,
            shortName: 'loading',
            isEnabled: false,
            hasStarted: false,
            needsRestart: false,
            timezone: 'UTC',
            offlineText: null,
            maxBitrate: 0,
            maxMounts: 0,
            maxHlsStreams: 0,
            enablePublicPages: true,
            publicPageUrl: '',
            enableOnDemand: false,
            onDemandUrl: '',
            webDjUrl: '',
            enableRequests: false,
            features: {
                media: false,
                sftp: false,
                podcasts: false,
                streamers: false,
                webhooks: false,
                mountPoints: false,
                hlsStreams: false,
                remoteRelays: false,
                customLiquidsoapConfig: false,
                autoDjQueue: false
            }
        }
    });
}
