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
                        <b-list-group-item
                            v-for="(item, itemKey) in panel.items"
                            :key="itemKey"
                            :href="item.url"
                        >
                            {{
                                item.label
                            }}
                        </b-list-group-item>
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
                                class="btn btn-dark btn-sm py-2"
                                @click.prevent="showMemoryStatsHelpModal"
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

                        <b-progress
                            :max="stats.memory.bytes.total"
                            :label="stats.memory.readable.used"
                            class="h-20 mb-3 mt-2"
                        >
                            <b-progress-bar
                                variant="primary"
                                :value="stats.memory.bytes.used"
                            />
                            <b-progress-bar
                                variant="warning"
                                :value="stats.memory.bytes.cached"
                            />
                        </b-progress>

                        <div class="row">
                            <div class="col">
                                <b-badge
                                    pill
                                    variant="primary"
                                >
                                    &nbsp;&nbsp;
                                </b-badge>&nbsp;
                                {{ $gettext('Used') }}
                                : {{ stats.memory.readable.used }}
                            </div>
                            <div class="col">
                                <b-badge
                                    pill
                                    variant="warning"
                                >
                                    &nbsp;&nbsp;
                                </b-badge>&nbsp;
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

                        <b-progress
                            :max="stats.disk.bytes.total"
                            :label="stats.disk.readable.used"
                            class="h-20 mb-3 mt-2"
                        >
                            <b-progress-bar
                                variant="primary"
                                :value="stats.disk.bytes.used"
                            />
                        </b-progress>

                        <div class="row">
                            <div class="col">
                                <b-badge
                                    pill
                                    variant="primary"
                                >
                                    &nbsp;&nbsp;
                                </b-badge>&nbsp;
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
                                class="btn btn-dark btn-sm py-2"
                                @click.prevent="showCpuStatsHelpModal"
                            >
                                <icon icon="help_outline" />
                            </button>
                        </div>
                    </div>

                    <div class="card-body">
                        <h5 class="mb-1 text-center">
                            {{ formatCpuName(stats.cpu.total.name) }}
                        </h5>

                        <b-progress
                            max="100"
                            :label="formatPercentageString(stats.cpu.total.usage)"
                            class="h-20 mb-3 mt-2"
                        >
                            <b-progress-bar
                                variant="danger"
                                :value="stats.cpu.total.steal"
                            />
                            <b-progress-bar
                                variant="warning"
                                :value="stats.cpu.total.io_wait"
                            />
                            <b-progress-bar
                                variant="primary"
                                :value="stats.cpu.total.usage"
                            />
                        </b-progress>

                        <div class="row">
                            <div class="col">
                                <b-badge
                                    pill
                                    variant="danger"
                                >
                                    &nbsp;&nbsp;
                                </b-badge>&nbsp;
                                {{ $gettext('Steal') }}
                                : {{ stats.cpu.total.steal }}%
                            </div>
                            <div class="col">
                                <b-badge
                                    pill
                                    variant="warning"
                                >
                                    &nbsp;&nbsp;
                                </b-badge>&nbsp;
                                {{ $gettext('Wait') }}
                                : {{ stats.cpu.total.io_wait }}%
                            </div>
                            <div class="col">
                                <b-badge
                                    pill
                                    variant="primary"
                                >
                                    &nbsp;&nbsp;
                                </b-badge>&nbsp;
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

                                <b-progress
                                    max="100"
                                    :label="formatPercentageString(core.usage)"
                                    class="h-20"
                                >
                                    <b-progress-bar
                                        variant="danger"
                                        :value="core.steal"
                                    />
                                    <b-progress-bar
                                        variant="warning"
                                        :value="core.io_wait"
                                    />
                                    <b-progress-bar
                                        variant="primary"
                                        :value="core.usage"
                                    />
                                </b-progress>

                                <div class="b-row mb-2 mt-1">
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
                            <div class="row">
                                <h6>1-Min</h6>
                                {{ stats.cpu.load[0].toFixed(2) }}
                            </div>
                            <div class="row">
                                <h6>5-Min</h6>
                                {{ stats.cpu.load[1].toFixed(2) }}
                            </div>
                            <div class="row">
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
                                        class="btn btn-sm"
                                        :class="service.running ? 'btn-dark' : 'btn-danger'"
                                        @click.prevent="doRestart(service.links.restart)"
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
                                        <b-table
                                            striped
                                            responsive
                                            :items="getNetworkInterfaceTableItems(netInterface.received)"
                                            :fields="getNetworkInterfaceTableFields(netInterface.received)"
                                        />
                                    </div>
                                    <div class="col">
                                        <h5 class="mb-1 text-center">
                                            {{ $gettext('Transmitted') }}
                                        </h5>
                                        <b-table
                                            striped
                                            responsive
                                            :items="getNetworkInterfaceTableItems(netInterface.transmitted)"
                                            :fields="getNetworkInterfaceTableFields(netInterface.transmitted)"
                                        />
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
import {isObject, upperFirst} from 'lodash';
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

const getNetworkInterfaceTableFields = (interfaceData) => {
    let fields = [];

    Object.keys(interfaceData).forEach((key) => {
        fields.push({
            key: key,
            sortable: false
        });
    });

    return fields;
};

const getNetworkInterfaceTableItems = (interfaceData) => {
    let item = {};

    Object.entries(interfaceData).forEach((data) => {
        let key = data[0];
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
</script>
