<template>
    <loading
        v-if="stationData.isEnabled"
        :loading="propsLoading || servicesLoading || propsPlaceholder"
        lazy
    >
        <enabled-profile/>
    </loading>
    <station-disabled-panel v-else />
</template>

<script setup lang="ts">
import StationDisabledPanel from "~/components/Stations/Profile/StationDisabledPanel.vue";
import Loading from "~/components/Common/Loading.vue";
import EnabledProfile from "~/components/Stations/Profile/EnabledProfile.vue";
import {useStationData} from "~/functions/useStationQuery.ts";
import {useProfilePropsQuery, useProfileServicesQuery} from "~/components/Stations/Profile/useProfileQuery.ts";

const stationData = useStationData();

const {isLoading: propsLoading, isPlaceholderData: propsPlaceholder} = useProfilePropsQuery();
const {isLoading: servicesLoading} = useProfileServicesQuery();
</script>
