<template>
    <div>
        <h2 class="outside-card-header mb-1">
            {{ $gettext('Administration') }}
        </h2>

        <div class="row row-of-cards">
            <div
                v-for="panel in menuItems"
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
                <memory-stats-panel :stats="stats" />
            </div>

            <div class="col-sm-12 col-lg-6 col-xl-6">
                <disk-usage-panel :stats="stats" />
            </div>
        </div>

        <div class="row row-of-cards">
            <div class="col-sm-12 col-lg-8 col-xl-6">
                <cpu-stats-panel :stats="stats" />
            </div>

            <div class="col-sm-12 col-lg-4 col-xl-6">
                <services-panel />
            </div>
        </div>

        <div class="row row-of-cards">
            <div class="col">
                <network-stats-panel :stats="stats" />
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import Icon from '~/components/Common/Icon';
import {onMounted, onScopeDispose, shallowRef} from "vue";
import {useAxios} from "~/vendor/axios";
import {getApiUrl} from "~/router";
import {useAdminMenu} from "~/components/Admin/menu";
import CpuStatsPanel from "~/components/Admin/Index/CpuStatsPanel.vue";
import MemoryStatsPanel from "~/components/Admin/Index/MemoryStatsPanel.vue";
import DiskUsagePanel from "~/components/Admin/Index/DiskUsagePanel.vue";
import ServicesPanel from "~/components/Admin/Index/ServicesPanel.vue";
import NetworkStatsPanel from "~/components/Admin/Index/NetworkStatsPanel.vue";

const statsUrl = getApiUrl('/admin/server/stats');

const menuItems = useAdminMenu();

const stats = shallowRef({
    cpu: {
        total: {
            name: 'Total',
            steal: 0,
            io_wait: 0,
            usage: 0
        },
        cores: [],
        load: [
            0,
            0,
            0
        ]
    },
    memory: {
        bytes: {
            total: 0,
            used: 0,
            cached: 0
        },
        readable: {
            total: '',
            used: '',
            cached: ''
        }
    },
    disk: {
        bytes: {
            total: 0,
            used: 0
        },
        readable: {
            total: '',
            used: ''
        }
    },
    network: []
});

const {axios} = useAxios();

let statsTimeout = null;

const clearUpdates = () => {
    if (statsTimeout) {
        clearTimeout(statsTimeout);
        statsTimeout = null;
    }
};

onScopeDispose(clearUpdates);

const updateStats = () => {
    axios.get(statsUrl.value).then((response) => {
        stats.value = response.data;

        clearUpdates();
        statsTimeout = setTimeout(updateStats, (!document.hidden) ? 1000 : 5000);
    }).catch((error) => {
        if (!error.response || error.response.data.code !== 403) {
            clearUpdates();
            statsTimeout = setTimeout(updateStats, (!document.hidden) ? 5000 : 10000);
        }
    });
};

onMounted(updateStats);
</script>
