<template>
    <dashboard-with-sidebar>
        <template #sidebar>
            <loading lazy :loading="isLoading || isPlaceholderData">
                <sidebar/>
            </loading>
        </template>
        <template #default>
            <loading lazy :loading="isLoading || isPlaceholderData">
                <router-view/>
            </loading>
        </template>
    </dashboard-with-sidebar>
</template>

<script setup lang="ts">
import Sidebar from "~/components/Stations/Sidebar.vue";
import {useStationQuery} from "~/functions/useStationQuery.ts";
import Loading from "~/components/Common/Loading.vue";
import DashboardWithSidebar from "~/components/Layout/DashboardWithSidebar.vue";

const {
    isLoading,
    isPlaceholderData
} = useStationQuery();
</script>
