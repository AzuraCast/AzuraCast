<template>
    <div>
        <h2 class="outside-card-header mb-1">
            {{ $gettext('Administration') }}
        </h2>

        <div class="row row-of-cards">
            <div
                v-for="(panel, key) in adminPanels"
                :key="key"
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
                        <a
                            v-for="(item, itemKey) in panel.items"
                            :key="itemKey"
                            :href="item.url"
                            class="list-group-item list-group-item-action"
                        >
                            {{ item.label }}
                        </a>
                    </div>
                </section>
            </div>
        </div>

        <h2 class="outside-card-header mb-1">
            {{ $gettext('Server Status') }}
        </h2>

        <div class="row row-of-cards">
            <div class="col-sm-12 col-lg-6 col-xl-6">
                <section class="card">
                    <div class="card-header text-bg-primary d-flex align-items-center">
                        <div class="flex-fill">
                            <h2 class="card-title">
                                {{ $gettext('Memory') }}
                            </h2>
                        </div>

                        <div class="flex-shrink-0">
                            <button
                                type="button"
                                class="btn btn-dark btn-sm py-2"
                                @click="showMemoryStatsHelpModal"
                            >
                                <icon icon="help_outline" />
                            </button>
                        </div>
                    </div>

                    <div class="card-body">
                        <h6 class="mb-1 text-center">
                            {{ $gettext('Total RAM') }}
                            :
                            {{ stats.memory.readable.total }}
                        </h6>

                        <div
                            class="progress h-20 mb-3 mt-2"
                            role="progressbar"
                            :aria-label="stats.memory.readable.used"
                            aria-valuemin="0"
                            :aria-valuemax="stats.memory.bytes.total"
                        >
                            <div
                                class="progress-bar text-bg-primary"
                                :style="{ width: getPercent(stats.memory.bytes.used, stats.memory.bytes.total) }"
                            />
                            <div
                                class="progress-bar text-bg-warning"
                                :style="{ width: getPercent(stats.memory.bytes.cached, stats.memory.bytes.total) }"
                            />
                        </div>

                        <div class="row">
                            <div class="col">
                                <span class="badge text-bg-primary me-1">&nbsp;&nbsp;</span>
                                {{ $gettext('Used') }}
                                : {{ stats.memory.readable.used }}
                            </div>
                            <div class="col">
                                <span class="badge text-bg-warning me-1">&nbsp;&nbsp;</span>&nbsp;

                                {{ $gettext('Cached') }}
                                : {{ stats.memory.readable.cached }}
                            </div>
                        </div>
                    </div>
                </section>
            </div>

            <div class="col-sm-12 col-lg-6 col-xl-6">
                <section class="card">
                    <div class="card-header text-bg-primary">
                        <h2 class="card-title">
                            {{ $gettext('Disk Space') }}
                        </h2>
                    </div>

                    <div class="card-body">
                        <h6 class="mb-1 text-center">
                            {{ $gettext('Total Disk Space') }}
                            :
                            {{ stats.disk.readable.total }}
                        </h6>

                        <div
                            class="progress h-20 mb-3 mt-2"
                            role="progressbar"
                            :aria-label="stats.disk.readable.used"
                            aria-valuemin="0"
                            :aria-valuemax="stats.disk.bytes.total"
                        >
                            <div
                                class="progress-bar text-bg-primary"
                                :style="{ width: getPercent(stats.disk.bytes.used, stats.disk.bytes.total) }"
                            />
                        </div>

                        <div class="row">
                            <div class="col">
                                <span class="badge text-bg-primary me-1">&nbsp;&nbsp;</span>

                                {{ $gettext('Used') }}
                                :
                                {{ stats.disk.readable.used }}
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>

        <div class="row row-of-cards">
            <div class="col-sm-12 col-lg-8 col-xl-6">
                <section class="card">
                    <div class="card-header text-bg-primary d-flex align-items-center">
                        <div class="flex-fill">
                            <h2 class="card-title">
                                {{ $gettext('CPU Load') }}
                            </h2>
                        </div>

                        <div class="flex-shrink-0">
                            <button
                                type="button"
                                class="btn btn-dark btn-sm py-2"
                                @click="showCpuStatsHelpModal"
                            >
                                <icon icon="help_outline" />
                            </button>
                        </div>
                    </div>

                    <div class="card-body">
                        <h5 class="mb-1 text-center">
                            {{ formatCpuName(stats.cpu.total.name) }}
                        </h5>

                        <div
                            class="progress h-20 mb-3 mt-2"
                            role="progressbar"
                            :aria-label="formatPercentageString(stats.cpu.total.usage)"
                            aria-valuemin="0"
                            aria-valuemax="100"
                        >
                            <div
                                class="progress-bar text-bg-danger"
                                :style="{ width: stats.cpu.total.steal+'%' }"
                            />
                            <div
                                class="progress-bar text-bg-warning"
                                :style="{ width: stats.cpu.total.io_wait+'%' }"
                            />
                            <div
                                class="progress-bar text-bg-primary"
                                :style="{ width: stats.cpu.total.usage+'%' }"
                            />
                        </div>

                        <div class="row">
                            <div class="col">
                                <span class="badge text-bg-danger me-1">&nbsp;&nbsp;</span>
                                {{ $gettext('Steal') }}
                                : {{ stats.cpu.total.steal }}%
                            </div>
                            <div class="col">
                                <span class="badge text-bg-warning me-1">&nbsp;&nbsp;</span>
                                {{ $gettext('Wait') }}
                                : {{ stats.cpu.total.io_wait }}%
                            </div>
                            <div class="col">
                                <span class="badge text-bg-primary me-1">&nbsp;&nbsp;</span>
                                {{ $gettext('Use') }}
                                : {{ stats.cpu.total.usage }}%
                            </div>
                        </div>

                        <hr>

                        <div class="row">
                            <div
                                v-for="core in stats.cpu.cores"
                                :key="core.name"
                                class="col-lg-6"
                            >
                                <h6 class="mb-1 text-center">
                                    {{ formatCpuName(core.name) }}
                                </h6>

                                <div
                                    class="progress h-20 mb-3 mt-2"
                                    role="progressbar"
                                    :aria-label="formatPercentageString(core.usage)"
                                    aria-valuemin="0"
                                    aria-valuemax="100"
                                >
                                    <div
                                        class="progress-bar text-bg-danger"
                                        :style="{ width: core.steal+'%' }"
                                    />
                                    <div
                                        class="progress-bar text-bg-warning"
                                        :style="{ width: core.io_wait+'%' }"
                                    />
                                    <div
                                        class="progress-bar text-bg-primary"
                                        :style="{ width: core.usage+'%' }"
                                    />
                                </div>

                                <div class="row mb-2 mt-1">
                                    <div class="col">
                                        St: {{ core.steal }}%
                                    </div>
                                    <div class="col">
                                        Wa: {{ core.io_wait }}%
                                    </div>
                                    <div class="col">
                                        Us: {{ core.usage }}%
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer">
                        <h6 class="mb-1 text-center">
                            {{ $gettext('Load Average') }}
                        </h6>
                        <div class="row text-center">
                            <div class="col">
                                <h6>1-Min</h6>
                                {{ stats.cpu.load[0].toFixed(2) }}
                            </div>
                            <div class="col">
                                <h6>5-Min</h6>
                                {{ stats.cpu.load[1].toFixed(2) }}
                            </div>
                            <div class="col">
                                <h6>15-Min</h6>
                                {{ stats.cpu.load[2].toFixed(2) }}
                            </div>
                        </div>
                    </div>
                </section>
            </div>

            <div class="col-sm-12 col-lg-4 col-xl-6">
                <section class="card">
                    <div class="card-header text-bg-primary">
                        <h2 class="card-title mb-0">
                            {{ $gettext('Services') }}
                        </h2>
                    </div>

                    <table class="table table-striped table-responsive mb-0">
                        <colgroup>
                            <col style="width: 5%;">
                            <col style="width: 75%;">
                            <col style="width: 20%;">
                        </colgroup>
                        <tbody>
                            <tr
                                v-for="service in services"
                                :key="service.name"
                                class="align-middle"
                            >
                                <td class="text-center pe-2">
                                    <running-badge :running="service.running" />
                                </td>
                                <td class="ps-2">
                                    <h6 class="mb-0">
                                        {{ service.name }}<br>
                                        <small>{{ service.description }}</small>
                                    </h6>
                                </td>
                                <td>
                                    <button
                                        v-if="service.links.restart"
                                        type="button"
                                        class="btn btn-sm"
                                        :class="service.running ? 'btn-primary' : 'btn-danger'"
                                        @click="doRestart(service.links.restart)"
                                    >
                                        {{ $gettext('Restart') }}
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </section>
            </div>
        </div>

        <div class="row row-of-cards">
            <div class="col">
                <section class="card">
                    <div class="card-header text-bg-primary">
                        <h2 class="card-title mb-0">
                            {{ $gettext('Network Interfaces') }}
                        </h2>
                    </div>

                    <div class="card-body">
                        <o-tabs
                            nav-tabs-class="nav-tabs"
                            content-class="mt-3"
                        >
                            <o-tab-item
                                v-for="netInterface in stats.network"
                                :key="netInterface.interface_name"
                                :label="netInterface.interface_name"
                            >
                                <div class="row mb-3">
                                    <div class="col mb-3">
                                        <h5 class="mb-1 text-center">
                                            {{ $gettext('Received') }}
                                        </h5>

                                        <o-table
                                            striped
                                            class="table-responsive"
                                            :data="getNetworkInterfaceTableItems(netInterface.received)"
                                            :paginated="false"
                                        >
                                            <o-table-column
                                                v-for="key in getNetworkInterfaceTableFields(netInterface.received)"
                                                :key="key"
                                                v-slot="{ row }"
                                                :label="key"
                                                :sortable="false"
                                            >
                                                {{ get(row, key, null) }}
                                            </o-table-column>
                                        </o-table>
                                    </div>
                                    <div class="col">
                                        <h5 class="mb-1 text-center">
                                            {{ $gettext('Transmitted') }}
                                        </h5>

                                        <o-table
                                            striped
                                            class="table-responsive"
                                            :data="getNetworkInterfaceTableItems(netInterface.transmitted)"
                                            :paginated="false"
                                        >
                                            <o-table-column
                                                v-for="key in getNetworkInterfaceTableFields(netInterface.transmitted)"
                                                :key="key"
                                                v-slot="{ row }"
                                                :label="key"
                                                :sortable="false"
                                            >
                                                {{ get(row, key, null) }}
                                            </o-table-column>
                                        </o-table>
                                    </div>
                                </div>
                            </o-tab-item>
                        </o-tabs>
                    </div>
                </section>
            </div>
        </div>

        <cpu-stats-help-modal ref="$cpuStatsHelpModal" />
        <memory-stats-help-modal ref="$memoryStatsHelpModal" />
    </div>
</template>

<script setup>
import Icon from '~/components/Common/Icon';
import CpuStatsHelpModal from "./Index/CpuStatsHelpModal";
import MemoryStatsHelpModal from "./Index/MemoryStatsHelpModal";
import {get, isObject, upperFirst} from 'lodash';
import RunningBadge from "~/components/Common/Badges/RunningBadge.vue";
import {onMounted, ref, shallowRef} from "vue";
import {useAxios} from "~/vendor/axios";
import {useNotify} from "~/functions/useNotify";

const props = defineProps({
    adminPanels: {
        type: Object,
        required: true
    },
    statsUrl: {
        type: String,
        required: true
    },
    servicesUrl: {
        type: String,
        required: true
    }
});

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

const services = ref([]);

const formatCpuName = (cpuName) => upperFirst(cpuName);

const formatPercentageString = (value) => value + '%';

const getNetworkInterfaceTableFields = (interfaceData) => Object.keys(interfaceData);

const getNetworkInterfaceTableItems = (interfaceData) => {
    const item = {};

    Object.entries(interfaceData).forEach((data) => {
        const key = data[0];
        let value = data[1];

        if (isObject(value)) {
            value = value.readable + '/s';
        }

        item[key] = value;
    });

    return [item];
};

const {axios} = useAxios();

const updateStats = () => {
    axios.get(props.statsUrl).then((response) => {
        stats.value = response.data;

        setTimeout(updateStats, (!document.hidden) ? 1000 : 5000);
    }).catch((error) => {
        if (!error.response || error.response.data.code !== 403) {
            setTimeout(updateStats, (!document.hidden) ? 5000 : 10000);
        }
    });
};

onMounted(updateStats);

const updateServices = () => {
    axios.get(props.servicesUrl).then((response) => {
        services.value = response.data;

        setTimeout(updateServices, (!document.hidden) ? 5000 : 15000);
    }).catch((error) => {
        if (!error.response || error.response.data.code !== 403) {
            setTimeout(updateServices, (!document.hidden) ? 15000 : 30000);
        }
    });
};

onMounted(updateServices);

const {notifySuccess} = useNotify();

const doRestart = (serviceUrl) => {
    axios.post(serviceUrl).then((resp) => {
        notifySuccess(resp.data.message);
    });
};

const $cpuStatsHelpModal = ref(); // Template Ref

const showCpuStatsHelpModal = () => {
    $cpuStatsHelpModal.value.create();
};

const $memoryStatsHelpModal = ref(); // Template Ref

const showMemoryStatsHelpModal = () => {
    $memoryStatsHelpModal.value.create();
};

const getPercent = (amount, total) => {
    return ((amount / total) * 100) + '%';
}
</script>
