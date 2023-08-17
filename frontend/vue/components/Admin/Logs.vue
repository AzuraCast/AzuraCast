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
                <o-tabs
                    nav-tabs-class="nav-tabs"
                    content-class="mt-3"
                >
                    <o-tab-item
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
                    </o-tab-item>
                </o-tabs>
            </div>
        </card-page>
    </div>

    <streaming-log-modal ref="$modal" />
</template>

<script setup lang="ts">
import LogList from "~/components/Common/LogList";
import StreamingLogModal from "~/components/Common/StreamingLogModal";
import {Ref, ref} from "vue";
import CardPage from "~/components/Common/CardPage.vue";

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

const $modal: Ref<StreamingLogModal> = ref();

const viewLog = (url, isStreaming) => {
    $modal.value?.show(url, isStreaming);
};
</script>
