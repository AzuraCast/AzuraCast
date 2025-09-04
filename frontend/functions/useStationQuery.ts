import {useQuery, useQueryClient} from "@tanstack/vue-query";
import {BackendAdapters, FrontendAdapters, VueStationGlobals} from "~/entities/ApiInterfaces.ts";
import {computed, ComputedRef} from "vue";
import {useAxios} from "~/vendor/axios.ts";
import {QueryKeys, queryKeyWithStation} from "~/entities/Queries.ts";
import {useRoute} from "vue-router";
import {getStationApiUrl} from "~/router.ts";

export const useStationId = (): ComputedRef<number | null> => {
    const {params} = useRoute();
    return computed(() => Number(params.station_id) ?? null);
}

const blankStationGlobals: VueStationGlobals = {
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
    },
    ipGeoAttribution: 'N/A',
    backendType: BackendAdapters.None,
    frontendType: FrontendAdapters.Remote,
    canReload: false,
    useManualAutoDj: false
};

export const useStationQuery = () => {
    const {axios} = useAxios();
    const stationId = useStationId();

    const dashboardUrl = getStationApiUrl('/dashboard', stationId);

    return useQuery<VueStationGlobals>({
        queryKey: queryKeyWithStation(
            [
                QueryKeys.StationGlobals
            ],
            stationId
        ),
        queryFn: async ({signal}) => {
            const {data} = await axios.get<VueStationGlobals>(dashboardUrl.value, {signal});
            return data;
        },
        staleTime: 10 * 60 * 1000,
        placeholderData: blankStationGlobals
    });
}

export const useStationData = () => {
    const {data} = useStationQuery();
    return computed<VueStationGlobals>(() => data.value ?? blankStationGlobals);
};

export const useClearAllStationQueries = () => {
    const queryClient = useQueryClient();
    const stationId = useStationId();

    return async () => {
        await queryClient.invalidateQueries({
            queryKey: queryKeyWithStation([], stationId)
        });
    }
}
