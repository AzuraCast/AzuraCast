<template>
    <div>
        <h2 class="outside-card-header mb-1">
            {{ $gettext('Administration') }}
        </h2>

        <b-row>
            <b-col
                v-for="(panel, key) in adminPanels"
                :key="key"
                sm="12"
                lg="4"
                class="mb-4"
            >
                <b-card no-body>
                    <b-card-header
                        header-bg-variant="primary-dark"
                        class="d-flex align-items-center"
                    >
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
                    </b-card-header>

                    <b-list-group>
                        <b-list-group-item
                            v-for="(item, itemKey) in panel.items"
                            :key="itemKey"
                            :href="item.url"
                        >
                            {{
                                item.label
                            }}
                        </b-list-group-item>
                    </b-list-group>
                </b-card>
            </b-col>
        </b-row>

        <h2 class="outside-card-header mb-1">
            {{ $gettext('Server Status') }}
        </h2>

        <b-row>
            <b-col
                sm="12"
                lg="6"
                xl="6"
                class="mb-4"
            >
                <b-card no-body>
                    <b-card-header
                        header-bg-variant="primary-dark"
                        class="d-flex align-items-center"
                    >
                        <div class="flex-fill">
                            <h2 class="card-title">
                                {{ $gettext('Memory') }}
                            </h2>
                        </div>

                        <div class="flex-shrink-0">
                            <b-button
                                variant="outline-light"
                                size="sm"
                                class="py-2"
                                @click.prevent="showMemoryStatsHelpModal"
                            >
                                <icon icon="help_outline" />
                            </b-button>
                        </div>
                    </b-card-header>

                    <b-card-body>
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

                        <b-row>
                            <b-col>
                                <b-badge
                                    pill
                                    variant="primary"
                                >
&nbsp;&nbsp;
                                </b-badge>&nbsp;
                                {{ $gettext('Used') }}
                                : {{ stats.memory.readable.used }}
                            </b-col>
                            <b-col>
                                <b-badge
                                    pill
                                    variant="warning"
                                >
&nbsp;&nbsp;
                                </b-badge>&nbsp;
                                {{ $gettext('Cached') }}
                                : {{ stats.memory.readable.cached }}
                            </b-col>
                        </b-row>
                    </b-card-body>
                </b-card>
            </b-col>

            <b-col
                sm="12"
                lg="6"
                xl="6"
                class="mb-4"
            >
                <b-card no-body>
                    <b-card-header header-bg-variant="primary-dark">
                        <h2 class="card-title">
                            {{ $gettext('Disk Space') }}
                        </h2>
                    </b-card-header>

                    <b-card-body>
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

                        <b-row>
                            <b-col>
                                <b-badge
                                    pill
                                    variant="primary"
                                >
&nbsp;&nbsp;
                                </b-badge>&nbsp;
                                {{ $gettext('Used') }}
                                :
                                {{ stats.disk.readable.used }}
                            </b-col>
                        </b-row>
                    </b-card-body>
                </b-card>
            </b-col>
        </b-row>

        <b-row>
            <b-col
                sm="12"
                lg="8"
                xl="6"
                class="mb-4"
            >
                <b-card no-body>
                    <b-card-header
                        header-bg-variant="primary-dark"
                        class="d-flex align-items-center"
                    >
                        <div class="flex-fill">
                            <h2 class="card-title">
                                {{ $gettext('CPU Load') }}
                            </h2>
                        </div>

                        <div class="flex-shrink-0">
                            <b-button
                                variant="outline-light"
                                size="sm"
                                class="py-2"
                                @click.prevent="showCpuStatsHelpModal"
                            >
                                <icon icon="help_outline" />
                            </b-button>
                        </div>
                    </b-card-header>

                    <b-card-body>
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

                        <b-row>
                            <b-col>
                                <b-badge
                                    pill
                                    variant="danger"
                                >
&nbsp;&nbsp;
                                </b-badge>&nbsp;
                                {{ $gettext('Steal') }}
                                : {{ stats.cpu.total.steal }}%
                            </b-col>
                            <b-col>
                                <b-badge
                                    pill
                                    variant="warning"
                                >
&nbsp;&nbsp;
                                </b-badge>&nbsp;
                                {{ $gettext('Wait') }}
                                : {{ stats.cpu.total.io_wait }}%
                            </b-col>
                            <b-col>
                                <b-badge
                                    pill
                                    variant="primary"
                                >
&nbsp;&nbsp;
                                </b-badge>&nbsp;
                                {{ $gettext('Use') }}
                                : {{ stats.cpu.total.usage }}%
                            </b-col>
                        </b-row>

                        <hr>

                        <b-row>
                            <b-col
                                v-for="core in stats.cpu.cores"
                                :key="core.name"
                                lg="6"
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

                                <b-row
                                    no-gutters
                                    class="mb-2 mt-1"
                                >
                                    <b-col>
                                        St: {{ core.steal }}%
                                    </b-col>
                                    <b-col>
                                        Wa: {{ core.io_wait }}%
                                    </b-col>
                                    <b-col>
                                        Us: {{ core.usage }}%
                                    </b-col>
                                </b-row>
                            </b-col>
                        </b-row>
                    </b-card-body>

                    <b-card-footer>
                        <h6 class="mb-1 text-center">
                            {{ $gettext('Load Average') }}
                        </h6>
                        <b-row
                            class="text-center"
                            no-gutters
                        >
                            <b-col>
                                <h6>1-Min</h6>
                                {{ stats.cpu.load[0].toFixed(2) }}
                            </b-col>
                            <b-col>
                                <h6>5-Min</h6>
                                {{ stats.cpu.load[1].toFixed(2) }}
                            </b-col>
                            <b-col>
                                <h6>15-Min</h6>
                                {{ stats.cpu.load[2].toFixed(2) }}
                            </b-col>
                        </b-row>
                    </b-card-footer>
                </b-card>
            </b-col>

            <b-col
                sm="12"
                lg="4"
                xl="6"
                class="mb-4"
            >
                <b-card no-body>
                    <b-card-header
                        header-bg-variant="primary-dark"
                        class="d-flex align-items-center"
                    >
                        <div class="flex-fill">
                            <h2 class="card-title">
                                {{ $gettext('Services') }}
                            </h2>
                        </div>
                    </b-card-header>

                    <table class="table table-sm table-striped table-responsive mb-0">
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
                                <td class="text-center pr-2">
                                    <running-badge :running="service.running" />
                                </td>
                                <td class="pl-2">
                                    <h6 class="mb-0">
                                        {{ service.name }}<br>
                                        <small>{{ service.description }}</small>
                                    </h6>
                                </td>
                                <td>
                                    <b-button-group
                                        v-if="service.links.restart"
                                        size="sm"
                                    >
                                        <b-button
                                            size="sm"
                                            :variant="service.running ? 'bg' : 'danger'"
                                            @click.prevent="doRestart(service.links.restart)"
                                        >
                                            {{ $gettext('Restart') }}
                                        </b-button>
                                    </b-button-group>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </b-card>
            </b-col>
        </b-row>

        <b-row>
            <b-col>
                <b-card no-body>
                    <b-card-header header-bg-variant="primary-dark">
                        <h2 class="card-title">
                            {{ $gettext('Network Interfaces') }}
                        </h2>
                    </b-card-header>

                    <b-tabs
                        content-class="mt-3"
                        pills
                        card
                    >
                        <b-tab
                            v-for="netInterface in stats.network"
                            :key="netInterface.interface_name"
                            :title="netInterface.interface_name"
                        >
                            <b-row class="mb-3">
                                <b-col class="mb-3">
                                    <h5 class="mb-1 text-center">
                                        {{ $gettext('Received') }}
                                    </h5>
                                    <b-table
                                        striped
                                        responsive
                                        :items="getNetworkInterfaceTableItems(netInterface.received)"
                                        :fields="getNetworkInterfaceTableFields(netInterface.received)"
                                    />
                                </b-col>
                                <b-col>
                                    <h5 class="mb-1 text-center">
                                        {{ $gettext('Transmitted') }}
                                    </h5>
                                    <b-table
                                        striped
                                        responsive
                                        :items="getNetworkInterfaceTableItems(netInterface.transmitted)"
                                        :fields="getNetworkInterfaceTableFields(netInterface.transmitted)"
                                    />
                                </b-col>
                            </b-row>
                        </b-tab>
                    </b-tabs>
                </b-card>
            </b-col>
        </b-row>

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
import {useNotify} from "~/vendor/bootstrapVue";

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
