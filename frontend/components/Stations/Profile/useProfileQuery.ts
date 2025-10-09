import {useAxios} from "~/vendor/axios.ts";
import {useQuery, useQueryClient} from "@tanstack/vue-query";
import {QueryKeys, queryKeyWithStation} from "~/entities/Queries.ts";
import {StationProfileRequired} from "~/entities/StationProfile.ts";
import NowPlaying from "~/entities/NowPlaying.ts";
import {ApiStationsVueProfileProps} from "~/entities/ApiInterfaces.ts";
import {useStationData, useStationId} from "~/functions/useStationQuery.ts";
import {computed} from "vue";
import {toRefs} from "@vueuse/core";
import {useApiRouter} from "~/functions/useApiRouter.ts";

const blankProps: ApiStationsVueProfileProps = {
    nowPlayingProps: {
        stationShortName: '',
        useStatic: false,
        useSse: false
    },
    publicPageEmbedUrl: "",
    publicOnDemandEmbedUrl: "",
    publicRequestEmbedUrl: "",
    publicHistoryEmbedUrl: "",
    publicScheduleEmbedUrl: "",
    publicPodcastsEmbedUrl: "",
    frontendAdminUri: "",
    frontendAdminPassword: "",
    frontendSourcePassword: "",
    frontendRelayPassword: "",
    frontendPort: null
}

export const useProfilePropsQuery = () => {
    const {axios} = useAxios();
    const {getStationApiUrl} = useApiRouter();

    const apiUrl = getStationApiUrl('/vue/profile');

    const stationData = useStationData();
    const {isEnabled} = toRefs(stationData);

    return useQuery<ApiStationsVueProfileProps>({
        queryKey: queryKeyWithStation([
            QueryKeys.StationProfile,
            'props'
        ]),
        queryFn: async ({signal}) => {
            const {data} = await axios.get<ApiStationsVueProfileProps>(apiUrl.value, {signal});
            return data;
        },
        placeholderData: () => blankProps,
        enabled: isEnabled,
    })
};

const blankServices: StationProfileRequired = {
    station: {
        ...NowPlaying.station
    },
    services: {
        backendRunning: false,
        frontendRunning: false
    },
    schedule: []
}

export const useProfileServicesQuery = () => {
    const {axiosSilent} = useAxios();
    const {getStationApiUrl} = useApiRouter();

    const profileApiUrl = getStationApiUrl('/profile');

    const stationData = useStationData();
    const {isEnabled} = toRefs(stationData);

    return useQuery<StationProfileRequired>({
        queryKey: queryKeyWithStation([
            QueryKeys.StationProfile,
            'profile'
        ]),
        queryFn: async ({signal}) => {
            const {data} = await axiosSilent.get(profileApiUrl.value, {signal});
            return data;
        },
        placeholderData: () => blankServices,
        refetchInterval: 15 * 1000,
        enabled: isEnabled,
    });
};

export const useStationProfileData = () => {
    const {data: propsData} = useProfilePropsQuery();
    const {data: servicesData} = useProfileServicesQuery();

    return computed<ApiStationsVueProfileProps & StationProfileRequired>(() => {
        const props = propsData.value ?? blankProps;
        const services = servicesData.value ?? blankServices;

        return {
            ...props,
            ...services
        };
    });
};

export const useClearProfileData = () => {
    const queryClient = useQueryClient();
    const stationId = useStationId();

    return async () => {
        await queryClient.invalidateQueries({
            queryKey: queryKeyWithStation([
                QueryKeys.StationProfile
            ], stationId)
        });
    };
}
