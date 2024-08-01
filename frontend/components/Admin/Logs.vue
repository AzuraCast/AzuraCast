<template>
    <div class="row-of-cards">
        <card-page
            header-id="hdr_system_logs"
            :title="$gettext('System Logs')"
        >
            <log-list
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
import {Ref, ref} from "vue";
import CardPage from "~/components/Common/CardPage.vue";
import Tabs from "~/components/Common/Tabs.vue";
import Tab from "~/components/Common/Tab.vue";

defineProps({
    systemLogsUrl: {
        type: String,
        required: true,
    },
    stationLogs: {
        type: Array,
        default: () => {
            return [];
        }
    }
});

const $modal: Ref<InstanceType<typeof StreamingLogModal> | null> = ref(null);

const viewLog = (url, isStreaming) => {
    $modal.value?.show(url, isStreaming);
};
</script>
