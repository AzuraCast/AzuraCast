<template>
    <section class="card">
        <div class="card-header text-bg-primary">
            <h2 class="card-title mb-0">
                {{ $gettext('Network Interfaces') }}
            </h2>
        </div>

        <div class="card-body">
            <tabs>
                <tab
                    v-for="netInterface in stats.network"
                    :key="netInterface.interface_name"
                    :label="netInterface.interface_name"
                >
                    <div class="row mb-3">
                        <div class="col mb-3">
                            <h5 class="mb-1 text-center">
                                {{ $gettext('Received') }}
                            </h5>

                            <network-stats-table
                                :fields="getNetworkInterfaceTableFields(netInterface.received)"
                                :data="getNetworkInterfaceTableItems(netInterface.received)"
                            />
                        </div>
                        <div class="col">
                            <h5 class="mb-1 text-center">
                                {{ $gettext('Transmitted') }}
                            </h5>

                            <network-stats-table
                                :fields="getNetworkInterfaceTableFields(netInterface.transmitted)"
                                :data="getNetworkInterfaceTableItems(netInterface.transmitted)"
                            />
                        </div>
                    </div>
                </tab>
            </tabs>
        </div>
    </section>
</template>

<script setup lang="ts">
import Tab from "~/components/Common/Tab.vue";
import Tabs from "~/components/Common/Tabs.vue";
import {isObject} from "lodash";
import NetworkStatsTable from "~/components/Admin/Index/NetworkStatsTable.vue";

const props = defineProps<{
    stats: object
}>();

const getNetworkInterfaceTableFields = (interfaceData) => Object.keys(interfaceData);

const getNetworkInterfaceTableItems = (interfaceData) => {
    const item = {};

    Object.entries(interfaceData).forEach((data) => {
        const key = data[0];
        let value: any = data[1];

        if (isObject(value)) {
            value = value.readable + '/s';
        }

        item[key] = value;
    });

    return [item];
};
</script>
