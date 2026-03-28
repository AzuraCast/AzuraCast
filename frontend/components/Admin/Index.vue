<template>
    <dashboard-no-sidebar>
        <h2 class="outside-card-header mb-1">
            {{ $gettext('Administration') }}
        </h2>

        <div class="row row-of-cards">
            <div class="col-sm-12 col-lg-4">
                <menu-block :items="menuItems.slice(0, 1)"/>
            </div>
            <div class="col-sm-12 col-lg-4">
                <menu-block :items="menuItems.slice(1, 2)"/>
            </div>
            <div class="col-sm-12 col-lg-4">
                <menu-block :items="menuItems.slice(2)"/>
            </div>
        </div>

        <h2 class="outside-card-header mb-1">
            {{ $gettext('Server Status') }}
        </h2>

        <div class="row row-of-cards">
            <div class="col-sm-12 col-lg-6 col-xl-6">
                <loading
                    :loading="isLoading"
                    lazy
                >
                    <memory-stats-panel
                        v-if="stats && stats.memory"
                        :memory-stats="stats.memory"
                    />
                </loading>
            </div>

            <div class="col-sm-12 col-lg-6 col-xl-6">
                <loading
                    :loading="isLoading"
                    lazy
                >
                    <disk-usage-panel
                        v-if="stats && stats.disk"
                        :disk-stats="stats.disk"
                    />
                </loading>
            </div>
        </div>

        <div class="row row-of-cards">
            <div class="col-sm-12 col-lg-8 col-xl-6">
                <loading
                    :loading="isLoading"
                    lazy
                >
                    <cpu-stats-panel
                        v-if="stats && stats.cpu"
                        :cpu-stats="stats.cpu"
                    />
                </loading>
            </div>

            <div class="col-sm-12 col-lg-4 col-xl-6">
                <services-panel />
            </div>
        </div>

        <div class="row row-of-cards">
            <div class="col">
                <loading
                    :loading="isLoading"
                    lazy
                >
                    <network-stats-panel
                        v-if="stats && stats.network"
                        :network-stats="stats.network"
                    />
                </loading>
            </div>
        </div>
    </dashboard-no-sidebar>
</template>

<script setup lang="ts">
import {useAxios} from "~/vendor/axios";
import {useAdminMenu} from "~/components/Admin/menu";
import CpuStatsPanel from "~/components/Admin/Index/CpuStatsPanel.vue";
import MemoryStatsPanel from "~/components/Admin/Index/MemoryStatsPanel.vue";
import DiskUsagePanel from "~/components/Admin/Index/DiskUsagePanel.vue";
import ServicesPanel from "~/components/Admin/Index/ServicesPanel.vue";
import NetworkStatsPanel from "~/components/Admin/Index/NetworkStatsPanel.vue";
import Loading from "~/components/Common/Loading.vue";
import {ApiAdminServerStats} from "~/entities/ApiInterfaces.ts";
import {useQuery} from "@tanstack/vue-query";
import {QueryKeys} from "~/entities/Queries.ts";
import DashboardNoSidebar from "~/components/Layout/DashboardNoSidebar.vue";
import {useApiRouter} from "~/functions/useApiRouter.ts";
import MenuBlock from "~/components/Admin/Index/MenuBlock.vue";

const {getApiUrl} = useApiRouter();
const statsUrl = getApiUrl('/admin/server/stats');

const menuItems = useAdminMenu();

const {axiosSilent} = useAxios();

const {data: stats, isLoading} = useQuery<ApiAdminServerStats>({
    queryKey: [QueryKeys.AdminIndex, 'stats'],
    queryFn: async ({signal}) => {
        const {data} = await axiosSilent.get(statsUrl.value, {signal});
        return data;
    },
    placeholderData: () => ({
        cpu: {
            total: {
                name: "Total",
                usage: "",
                idle: "",
                io_wait: "",
                steal: "",
            },
            cores: [],
            load: [0, 0, 0]
        },
        memory: {
            total_bytes: "0",
            total_readable: "",
            free_bytes: "0",
            free_readable: "",
            buffers_bytes: "0",
            buffers_readable: "",
            cached_bytes: "0",
            cached_readable: "",
            sReclaimable_bytes: "0",
            sReclaimable_readable: "",
            shmem_bytes: "0",
            shmem_readable: "",
            used_bytes: "0",
            used_readable: ""
        },
        swap: {
            total_bytes: "0",
            total_readable: "",
            free_bytes: "0",
            free_readable: "",
            used_bytes: "0",
            used_readable: ""
        },
        disk: {
            total_bytes: "0",
            total_readable: "",
            free_bytes: "0",
            free_readable: "",
            used_bytes: "0",
            used_readable: ""
        },
        network: []
    }),
    refetchInterval: 5 * 1000
});
</script>
