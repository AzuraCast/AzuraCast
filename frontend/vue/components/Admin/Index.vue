<template>
    <div>
        <h2 class="outside-card-header mb-1">
            <translate key="lang_hdr_admin">Administration</translate>
        </h2>

        <b-row>
            <b-col v-for="(panel, key) in adminPanels" :key="key" sm="12" lg="4" class="mb-4">
                <b-card no-body>
                    <b-card-header header-bg-variant="primary-dark" class="d-flex align-items-center">
                        <div class="flex-fill">
                            <h2 class="card-title">{{ panel.label }}</h2>
                        </div>
                        <div class="flex-shrink-0 pt-1">
                            <icon class="lg" :icon="panel.icon"></icon>
                        </div>
                    </b-card-header>

                    <b-list-group>
                        <b-list-group-item v-for="(item, key) in panel.items" :key="key" :href="item.url">{{
                                item.label
                            }}
                        </b-list-group-item>
                    </b-list-group>
                </b-card>
            </b-col>
        </b-row>

        <h2 class="outside-card-header mb-1">
            <translate key="lang_hdr_server_status">Server Status</translate>
        </h2>

        <b-row>
            <b-col sm="12" lg="6" xl="6" class="mb-4">
                <b-card no-body>
                    <b-card-header header-bg-variant="primary-dark" class="d-flex align-items-center">
                        <div class="flex-fill">
                            <h2 class="card-title">
                                <translate key="lang_hdr_memory">Memory</translate>
                            </h2>
                        </div>

                        <div class="flex-shrink-0">
                            <b-button variant="outline-light" size="sm" class="py-2"
                                      @click.prevent="showMemoryStatsHelpModal">
                                <icon icon="help_outline"></icon>
                            </b-button>
                        </div>
                    </b-card-header>

                    <b-card-body>
                        <h6 class="mb-1 text-center">
                            <translate key="lang_disk_header">Total RAM</translate>
                            :
                            {{ stats.memory.readable.total }}
                        </h6>

                        <b-progress :max="stats.memory.bytes.total" :label="stats.memory.readable.used"
                                    class="h-20 mb-3 mt-2">
                            <b-progress-bar variant="primary" :value="stats.memory.bytes.used"></b-progress-bar>
                            <b-progress-bar variant="warning"
                                            :value="stats.memory.bytes.cached"></b-progress-bar>
                        </b-progress>

                        <b-row>
                            <b-col>
                                <b-badge pill variant="primary">&nbsp;&nbsp;</b-badge>&nbsp;
                                <translate key="lang_memory_used">Used</translate>
                                : {{ stats.memory.readable.used }}
                            </b-col>
                            <b-col>
                                <b-badge pill variant="warning">&nbsp;&nbsp;</b-badge>&nbsp;
                                <translate key="lang_memory_cached">Cached</translate>
                                : {{ stats.memory.readable.cached }}
                            </b-col>
                        </b-row>
                    </b-card-body>
                </b-card>
            </b-col>

            <b-col sm="12" lg="6" xl="6" class="mb-4">
                <b-card no-body>
                    <b-card-header header-bg-variant="primary-dark">
                        <h2 class="card-title">
                            <translate key="lang_hdr_disk_space">Disk Space</translate>
                        </h2>
                    </b-card-header>

                    <b-card-body>
                        <h6 class="mb-1 text-center">
                            <translate key="lang_total_disk_space">Total Disk Space</translate>
                            :
                            {{ stats.disk.readable.total }}
                        </h6>

                        <b-progress :max="stats.disk.bytes.total" :label="stats.disk.readable.used"
                                    class="h-20 mb-3 mt-2">
                            <b-progress-bar variant="primary" :value="stats.disk.bytes.used"></b-progress-bar>
                        </b-progress>

                        <b-row>
                            <b-col>
                                <b-badge pill variant="primary">&nbsp;&nbsp;</b-badge>&nbsp;
                                <translate key="lang_used_disk_space">Used</translate>
                                :
                                {{ stats.disk.readable.used }}
                            </b-col>
                        </b-row>
                    </b-card-body>
                </b-card>
            </b-col>
        </b-row>

        <b-row>
            <b-col sm="12" lg="8" xl="6" class="mb-4">
                <b-card no-body>
                    <b-card-header header-bg-variant="primary-dark" class="d-flex align-items-center">
                        <div class="flex-fill">
                            <h2 class="card-title">
                                <translate key="lang_hdr_cpu_load">CPU Load</translate>
                            </h2>
                        </div>

                        <div class="flex-shrink-0">
                            <b-button variant="outline-light" size="sm" class="py-2"
                                      @click.prevent="showCpuStatsHelpModal">
                                <icon icon="help_outline"></icon>
                            </b-button>
                        </div>
                    </b-card-header>

                    <b-card-body>
                        <h5 class="mb-1 text-center">{{ formatCpuName(stats.cpu.total.name) }}</h5>

                        <b-progress max="100" :label="formatPercentageString(stats.cpu.total.usage)"
                                    class="h-20 mb-3 mt-2">
                            <b-progress-bar variant="danger" :value="stats.cpu.total.steal"></b-progress-bar>
                            <b-progress-bar variant="warning" :value="stats.cpu.total.io_wait"></b-progress-bar>
                            <b-progress-bar variant="primary" :value="stats.cpu.total.usage"></b-progress-bar>
                        </b-progress>

                        <b-row>
                            <b-col>
                                <b-badge pill variant="danger">&nbsp;&nbsp;</b-badge>&nbsp;
                                <translate key="lang_cpu_steal">Steal</translate>
                                : {{ stats.cpu.total.steal }}%
                            </b-col>
                            <b-col>
                                <b-badge pill variant="warning">&nbsp;&nbsp;</b-badge>&nbsp;
                                <translate key="lang_cpu_wait">Wait</translate>
                                : {{ stats.cpu.total.io_wait }}%
                            </b-col>
                            <b-col>
                                <b-badge pill variant="primary">&nbsp;&nbsp;</b-badge>&nbsp;
                                <translate key="lang_cpu_use">Use</translate>
                                : {{ stats.cpu.total.usage }}%
                            </b-col>
                        </b-row>

                        <hr>

                        <b-row>
                            <b-col v-for="core in stats.cpu.cores" :key="core.name" lg="6">
                                <h6 class="mb-1 text-center">{{ formatCpuName(core.name) }}</h6>

                                <b-progress max="100" :label="formatPercentageString(core.usage)" class="h-20">
                                    <b-progress-bar variant="danger" :value="core.steal"></b-progress-bar>
                                    <b-progress-bar variant="warning" :value="core.io_wait"></b-progress-bar>
                                    <b-progress-bar variant="primary" :value="core.usage"></b-progress-bar>
                                </b-progress>

                                <b-row no-gutters class="mb-2 mt-1">
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
                            <translate key="lang_cpu_average">Load Average</translate>
                        </h6>
                        <b-row class="text-center" no-gutters>
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

            <b-col sm="12" lg="4" xl="6" class="mb-4">
                <b-card no-body>
                    <b-card-header header-bg-variant="primary-dark" class="d-flex align-items-center">
                        <div class="flex-fill">
                            <h2 class="card-title">
                                <translate key="lang_hdr_services">Services</translate>
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
                        <tr class="align-middle" v-for="service in services" :key="service.name">
                            <td class="text-center pr-2">
                                <template v-if="service.running">
                                    <b-badge pill variant="success" :title="langServiceRunning">
                                        &nbsp;&nbsp;
                                        <span class="sr-only">{{ langServiceRunning }}</span>
                                    </b-badge>
                                </template>
                                <template v-else>
                                    <b-badge pill variant="danger" :title="langServiceStopped">
                                        &nbsp;&nbsp;
                                        <span class="sr-only">{{ langServiceStopped }}</span>
                                    </b-badge>
                                </template>
                            </td>
                            <td class="pl-2">
                                <h6 class="mb-0">
                                    {{ service.name }}<br>
                                    <small>{{ service.description }}</small>
                                </h6>
                            </td>
                            <td>
                                <b-button-group size="sm" v-if="service.links.restart">
                                    <b-button size="sm" :variant="service.running ? 'bg' : 'danger'"
                                              @click.prevent="doRestart(service.links.restart)">
                                        <translate key="lang_btn_restart">Restart</translate>
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
                            <translate key="lang_hdr_network_interfaces">Network Interfaces</translate>
                        </h2>
                    </b-card-header>

                    <b-tabs content-class="mt-3">
                        <b-tab v-for="netInterface in stats.network" :key="netInterface.interface_name"
                               :title="netInterface.interface_name" pills card>
                            <b-row class="mb-3">
                                <b-col class="mb-3">
                                    <h5 class="mb-1 text-center">
                                        <translate key="lang_net_received">Received</translate>
                                    </h5>
                                    <b-table striped responsive
                                             :items="getNetworkInterfaceTableItems(netInterface.received)"
                                             :fields="getNetworkInterfaceTableFields(netInterface.received)">
                                    </b-table>
                                </b-col>
                                <b-col>
                                    <h5 class="mb-1 text-center">
                                        <translate key="lang_net_transmitted">Transmitted</translate>
                                    </h5>
                                    <b-table striped responsive
                                             :items="getNetworkInterfaceTableItems(netInterface.transmitted)"
                                             :fields="getNetworkInterfaceTableFields(netInterface.transmitted)">
                                    </b-table>
                                </b-col>
                            </b-row>
                        </b-tab>
                    </b-tabs>
                </b-card>
            </b-col>
        </b-row>

        <cpu-stats-help-modal ref="cpuStatsHelpModal"></cpu-stats-help-modal>
        <memory-stats-help-modal ref="memoryStatsHelpModal"></memory-stats-help-modal>
    </div>
</template>

<script>
import Icon from '~/components/Common/Icon';
import InfoCard from '~/components/Common/InfoCard';
import CpuStatsHelpModal from "./Index/CpuStatsHelpModal";
import MemoryStatsHelpModal from "./Index/MemoryStatsHelpModal";
import _ from 'lodash';

export default {
    name: 'AdminIndex',
    components: {InfoCard, CpuStatsHelpModal, MemoryStatsHelpModal, Icon},
    props: {
        adminPanels: Object,
        statsUrl: String,
        servicesUrl: String
    },
    data() {
        return {
            stats: {
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
            },
            services: []
        };
    },
    created() {
        this.updateStats();
        this.updateServices();
    },
    computed: {
        langServiceRunning() {
            return this.$gettext('Service Running');
        },
        langServiceStopped() {
            return this.$gettext('Service Stopped');
        }
    },
    methods: {
        formatCpuName(cpuName) {
            return _.upperFirst(cpuName);
        },
        formatPercentageString(value) {
            return value + '%';
        },
        getNetworkInterfaceTableFields(interfaceData) {
            let fields = [];

            Object.keys(interfaceData).forEach((key) => {
                fields.push({
                    key: key,
                    sortable: false
                });
            });

            return fields;
        },
        getNetworkInterfaceTableItems(interfaceData) {
            let item = {};

            Object.entries(interfaceData).forEach((data) => {
                let key = data[0];
                let value = data[1];

                if (_.isObject(value)) {
                    value = value.readable + '/s';
                }

                item[key] = value;
            });

            return [item];
        },
        updateStats() {
            this.axios.get(this.statsUrl).then((response) => {
                this.stats = response.data;

                setTimeout(this.updateStats, (!document.hidden) ? 1000 : 5000);
            }).catch((error) => {
                if (!error.response || error.response.data.code !== 403) {
                    setTimeout(this.updateStats, (!document.hidden) ? 5000 : 10000);
                }
            });
        },
        updateServices() {
            this.axios.get(this.servicesUrl).then((response) => {
                this.services = response.data;

                setTimeout(this.updateServices, (!document.hidden) ? 5000 : 15000);
            }).catch((error) => {
                if (!error.response || error.response.data.code !== 403) {
                    setTimeout(this.updateServices, (!document.hidden) ? 15000 : 30000);
                }
            });
        },
        doRestart(serviceUrl) {
            this.axios.post(serviceUrl).then((resp) => {
                this.$notifySuccess(resp.data.message);
            });
        },
        showCpuStatsHelpModal() {
            this.$refs.cpuStatsHelpModal.create();
        },
        showMemoryStatsHelpModal() {
            this.$refs.memoryStatsHelpModal.create();
        },
    }
};
</script>
