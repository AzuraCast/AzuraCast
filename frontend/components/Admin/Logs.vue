<template>
    <div class="row-of-cards">
        <card-page
            header-id="hdr_system_logs"
            :title="$gettext('System Logs')"
        >
            <log-list
                :query-key="[QueryKeys.AdminDebug, 'logs']"
                :url="systemLogsUrl"
                @view="viewLog"
            />
        </card-page>

        <card-page
            v-if="stationLogs.length > 0"
            header-id="hdr_logs_by_station"
            :title="$gettext('Logs by Station')"
        >
            <div class="card-body">
                <tabs content-class="mt-3">
                    <tab
                        v-for="row in stationLogs"
                        :key="row.id"
                        :label="row.name"
                    >
                        <div class="card-body-flush">
                            <log-list
                                :query-key="[QueryKeys.AdminDebug, 'logs', row.id]"
                                :url="row.url"
                                @view="viewLog"
                            />
                        </div>
                    </tab>
                </tabs>
            </div>
        </card-page>
    </div>

    <streaming-log-modal ref="$modal" />
</template>

<script setup lang="ts">
import LogList from "~/components/Common/LogList.vue";
import StreamingLogModal from "~/components/Common/StreamingLogModal.vue";
import {useTemplateRef} from "vue";
import CardPage from "~/components/Common/CardPage.vue";
import Tabs from "~/components/Common/Tabs.vue";
import Tab from "~/components/Common/Tab.vue";
import {QueryKeys} from "~/entities/Queries.ts";

interface StationLogsItem {
    id: number,
    name: string,
    url: string
}

defineProps<{
    systemLogsUrl: string,
    stationLogs: StationLogsItem[]
}>();

const $modal = useTemplateRef('$modal');

const viewLog = (url: string, isStreaming: boolean) => {
    $modal.value?.show(url, isStreaming);
};
</script>
