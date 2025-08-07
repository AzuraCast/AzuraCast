<template>
    <loading
        v-if="isEnabled"
        :loading="isLoading"
        lazy
    >
        <enabled-profile v-bind="state" />
    </loading>
    <station-disabled-panel v-else />
</template>

<script setup lang="ts">
import {useAzuraCastStation} from "~/vendor/azuracast.ts";
import {useAxios} from "~/vendor/axios.ts";
import {getStationApiUrl} from "~/router.ts";
import StationDisabledPanel from "~/components/Stations/Profile/StationDisabledPanel.vue";
import Loading from "~/components/Common/Loading.vue";
import EnabledProfile from "~/components/Stations/Profile/EnabledProfile.vue";
import {useQuery} from "@tanstack/vue-query";
import {QueryKeys, queryKeyWithStation} from "~/entities/Queries.ts";

const {isEnabled} = useAzuraCastStation();

const {axios} = useAxios();

const apiUrl = getStationApiUrl('/vue/profile');

const {data: state, isLoading} = useQuery({
    queryKey: queryKeyWithStation([
        QueryKeys.StationProfile
    ], [
        'profile'
    ]),
    queryFn: async ({signal}) => {
        const {data} = await axios.get(apiUrl.value, {signal});
        return data;
    },
    enabled: isEnabled
});
</script>
