<template>
    <loading
        v-if="isEnabled"
        :loading="propsLoading"
        lazy
    >
        <enabled-profile v-if="props && profile" v-bind="props" :profile="profile"/>
    </loading>
    <station-disabled-panel v-else />
</template>

<script setup lang="ts">
import {useAxios} from "~/vendor/axios.ts";
import {getStationApiUrl} from "~/router.ts";
import StationDisabledPanel from "~/components/Stations/Profile/StationDisabledPanel.vue";
import Loading from "~/components/Common/Loading.vue";
import EnabledProfile, {EnabledProfileProps} from "~/components/Stations/Profile/EnabledProfile.vue";
import {useQuery} from "@tanstack/vue-query";
import {QueryKeys, queryKeyWithStation} from "~/entities/Queries.ts";
import {useStationData} from "~/functions/useStationQuery.ts";
import {toRefs} from "@vueuse/core";
import NowPlaying from "~/entities/NowPlaying.ts";
import {ApiStationProfile} from "~/entities/ApiInterfaces.ts";
import {DeepRequired} from "utility-types";

const stationData = useStationData();
const {isEnabled} = toRefs(stationData);

const {axios} = useAxios();

const apiUrl = getStationApiUrl('/vue/profile');
const profileApiUrl = getStationApiUrl('/profile');

const {data: props, isLoading: propsLoading} = useQuery<EnabledProfileProps>({
    queryKey: queryKeyWithStation([
        QueryKeys.StationProfile,
        'props'
    ]),
    queryFn: async ({signal}) => {
        const {data} = await axios.get<EnabledProfileProps>(apiUrl.value, {signal});
        return data;
    },
    enabled: isEnabled
});

const {axiosSilent} = useAxios();

const {data: profile} = useQuery<DeepRequired<ApiStationProfile>>({
    queryKey: queryKeyWithStation([
        QueryKeys.StationProfile,
        'profile'
    ]),
    queryFn: async ({signal}) => {
        const {data} = await axiosSilent.get(profileApiUrl.value, {signal});
        return data;
    },
    placeholderData: () => ({
        station: {
            ...NowPlaying.station
        },
        services: {
            backend_running: false,
            frontend_running: false,
            station_has_started: false,
            station_needs_restart: false
        },
        schedule: []
    }),
    refetchInterval: 15 * 1000
});
</script>
