<template>
    <div>
        <h2 class="outside-card-header mb-1">
            {{ $gettext('Administration') }}
        </h2>

        <div class="row row-of-cards">
            <div
                v-for="panel in menuItems.categories"
                :key="panel.key"
                class="col-sm-12 col-lg-4"
            >
                <section class="card">
                    <div class="card-header text-bg-primary d-flex align-items-center">
                        <div class="flex-fill">
                            <h2 class="card-title">
                                {{ panel.label }}
                            </h2>
                        </div>
                        <div class="flex-shrink-0 pt-1">
                            <icon
                                class="lg"
                                :icon="panel.icon"
                            />
                        </div>
                    </div>

                    <div class="list-group list-group-flush">
                        <router-link
                            v-for="item in panel.items"
                            :key="item.key"
                            :to="item.url"
                            class="list-group-item list-group-item-action"
                        >
                            {{ item.label }}
                        </router-link>
                    </div>
                </section>
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
                    <memory-stats-panel :memory-stats="stats.memory"/>
                </loading>
            </div>

            <div class="col-sm-12 col-lg-6 col-xl-6">
                <loading
                    :loading="isLoading"
                    lazy
                >
                    <disk-usage-panel :disk-stats="stats.disk"/>
                </loading>
            </div>
        </div>

        <div class="row row-of-cards">
            <div class="col-sm-12 col-lg-8 col-xl-6">
                <loading
                    :loading="isLoading"
                    lazy
                >
                    <cpu-stats-panel :cpu-stats="stats.cpu"/>
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
                    <network-stats-panel :network-stats="stats.network"/>
                </loading>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import Icon from "~/components/Common/Icon.vue";
import {useAxios} from "~/vendor/axios";
import {getApiUrl} from "~/router";
import {useAdminMenu} from "~/components/Admin/menu";
import CpuStatsPanel from "~/components/Admin/Index/CpuStatsPanel.vue";
import MemoryStatsPanel from "~/components/Admin/Index/MemoryStatsPanel.vue";
import DiskUsagePanel from "~/components/Admin/Index/DiskUsagePanel.vue";
import ServicesPanel from "~/components/Admin/Index/ServicesPanel.vue";
import NetworkStatsPanel from "~/components/Admin/Index/NetworkStatsPanel.vue";
import Loading from "~/components/Common/Loading.vue";
import useAutoRefreshingAsyncState from "~/functions/useAutoRefreshingAsyncState.ts";
import {DeepRequired} from "utility-types";
import {ApiAdminServerStats} from "~/entities/ApiInterfaces.ts";

const statsUrl = getApiUrl('/admin/server/stats');

const menuItems = useAdminMenu();

const {axiosSilent} = useAxios();

type ServerStats = DeepRequired<ApiAdminServerStats>

const {state: stats, isLoading} = useAutoRefreshingAsyncState<ServerStats>(
    () => axiosSilent.get(statsUrl.value).then(r => r.data),
    {
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
    },
    {
        shallow: true,
        timeout: 5000
    }
);
</script>
