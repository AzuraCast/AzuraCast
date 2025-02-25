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
                    v-for="netInterface in networkStats"
                    :key="netInterface.interface_name"
                    :label="netInterface.interface_name"
                >
                    <div class="row mb-3">
                        <div class="col mb-3">
                            <h5 class="mb-1 text-center">
                                {{ $gettext('Received') }}
                            </h5>

                            <network-stats-table :row="netInterface.received"/>
                        </div>
                        <div class="col">
                            <h5 class="mb-1 text-center">
                                {{ $gettext('Transmitted') }}
                            </h5>

                            <network-stats-table :row="netInterface.transmitted"/>
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
import NetworkStatsTable from "~/components/Admin/Index/NetworkStatsTable.vue";
import {ApiAdminServerStatsNetworkInterfaceStats} from "~/entities/ApiInterfaces.ts";

defineProps<{
    networkStats: ApiAdminServerStatsNetworkInterfaceStats[]
}>();
</script>
