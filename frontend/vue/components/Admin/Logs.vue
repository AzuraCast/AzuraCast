<template>
    <div class="card mb-3">
        <div class="card-header bg-primary-dark">
            <h2 class="card-title">
                {{ $gettext('System Logs') }}
            </h2>
        </div>

        <log-list
            :url="systemLogsUrl"
            @view="viewLog"
        />
    </div>

    <div
        v-if="stationLogs.length > 0"
        class="card"
    >
        <div class="card-header bg-primary-dark">
            <h2 class="card-title">
                {{ $gettext('Logs by Station') }}
            </h2>
        </div>

        <b-tabs
            pills
            lazy
            nav-class="card-header-pills"
            nav-wrapper-class="card-header"
        >
            <b-tab
                v-for="row in stationLogs"
                :key="row.id"
                :title="row.name"
            >
                <log-list
                    :url="row.url"
                    @view="viewLog"
                />
            </b-tab>
        </b-tabs>
    </div>

    <streaming-log-modal ref="$modal" />
</template>

<script setup>
import LogList from "~/components/Common/LogList";
import StreamingLogModal from "~/components/Common/StreamingLogModal";
import {ref} from "vue";

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

const $modal = ref(); // StreamingLogModal

const viewLog = (url) => {
    $modal.value.show(url);
};
</script>
