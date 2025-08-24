<template>
    <dashboard-with-sidebar>
        <template #sidebar>
            <loading lazy :loading="isFetching">
                <sidebar/>
            </loading>
        </template>
        <template #default>
            <loading lazy :loading="isFetching">
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
    isFetching
} = useStationQuery();
</script>
