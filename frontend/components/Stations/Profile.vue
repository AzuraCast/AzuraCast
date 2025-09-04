<template>
    <loading
        v-if="isEnabled"
        :loading="isLoading"
        lazy
    >
        <enabled-profile v-if="state" v-bind="state"/>
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

const stationData = useStationData();
const {isEnabled} = toRefs(stationData);

const {axios} = useAxios();

const apiUrl = getStationApiUrl('/vue/profile');

const {data: state, isLoading} = useQuery<EnabledProfileProps>({
    queryKey: queryKeyWithStation([
        QueryKeys.StationProfile,
        'profile'
    ]),
    queryFn: async ({signal}) => {
        const {data} = await axios.get<EnabledProfileProps>(apiUrl.value, {signal});
        return data;
    },
    enabled: isEnabled
});
</script>
