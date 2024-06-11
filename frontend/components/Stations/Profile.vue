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
import {useAsyncState} from "@vueuse/core";
import {getStationApiUrl} from "~/router.ts";
import StationDisabledPanel from "~/components/Stations/Profile/StationDisabledPanel.vue";
import Loading from "~/components/Common/Loading.vue";
import EnabledProfile from "~/components/Stations/Profile/EnabledProfile.vue";

const {isEnabled} = useAzuraCastStation();

const {axios} = useAxios();
const {isLoading, state} = useAsyncState(
    async () => {
        if (isEnabled) {
            const r = await axios.get(getStationApiUrl('/vue/profile').value);
            return await r.data;
        }
        return {};
    },
    {}
);
</script>
