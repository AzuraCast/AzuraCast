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
                    <memory-stats-panel :stats="stats" />
                </loading>
            </div>

            <div class="col-sm-12 col-lg-6 col-xl-6">
                <loading
                    :loading="isLoading"
                    lazy
                >
                    <disk-usage-panel :stats="stats" />
                </loading>
            </div>
        </div>

        <div class="row row-of-cards">
            <div class="col-sm-12 col-lg-8 col-xl-6">
                <loading
                    :loading="isLoading"
                    lazy
                >
                    <cpu-stats-panel :stats="stats" />
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
                    <network-stats-panel :stats="stats" />
                </loading>
            </div>
        </div>
    </div>
</template>

<script lang="ts">
interface AdminCpuCore {
    name: string,
    usage: string,
    idle: string,
    io_wait: string,
    steal: string,
}

interface AdminCpuStats {
    total: AdminCpuCore,
    cores: AdminCpuCore[],
    load: number[],
}

interface AdminMemoryStats {
    bytes: {
        total: string,
        free: string,
        buffers: string,
        cached: string,
        sReclaimable: string,
        shmem: string,
        used: string,
    },
    readable: {
        total: string,
        free: string,
        buffers: string,
        cached: string,
        sReclaimable: string,
        shmem: string,
        used: string,
    }
}

interface AdminStorageStats {
    bytes: {
        total: string,
        free: string,
        used: string,
    },
    readable: {
        total: string,
        free: string,
        used: string,
    }
}

interface AdminNetworkInterfaceStats {
    interface_name: string,
    received: {
        speed: {
            bytes: string,
            readable: string,
        },
        packets: string,
        errs: string,
        drop: string,
        fifo: string,
        frame: string,
        compressed: string,
        multicast: string,
    },
    transmitted: {
        speed: {
            bytes: string
            readable: string
        },
        packets: string,
        errs: string,
        drop: string,
        fifo: string,
        frame: string,
        carrier: string,
        compressed: string,
    }
}

export interface AdminStats {
    cpu: AdminCpuStats,
    memory: AdminMemoryStats,
    swap: AdminStorageStats,
    disk: AdminStorageStats,
    network: AdminNetworkInterfaceStats[]
}
</script>

<script setup lang="ts">
import Icon from '~/components/Common/Icon.vue';
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

const statsUrl = getApiUrl('/admin/server/stats');

const menuItems = useAdminMenu();

const {axiosSilent} = useAxios();

const {state: stats, isLoading} = useAutoRefreshingAsyncState<AdminStats>(
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
            bytes: {
                total: "0",
                free: "0",
                buffers: "0",
                cached: "0",
                sReclaimable: "0",
                shmem: "0",
                used: "0",
            },
            readable: {
                total: "",
                free: "",
                buffers: "",
                cached: "",
                sReclaimable: "",
                shmem: "",
                used: "",
            }
        },
        swap: {
            bytes: {
                total: "0",
                free: "0",
                used: "0",
            },
            readable: {
                total: "",
                free: "",
                used: "",
            }
        },
        disk: {
            bytes: {
                total: "0",
                free: "0",
                used: "0",
            },
            readable: {
                total: "",
                free: "",
                used: "",
            }
        },
        network: []
    },
    {
        shallow: true,
        timeout: 5000
    }
);
</script>
