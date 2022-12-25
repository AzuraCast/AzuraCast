<template>
    <div class="card mb-3">
        <div class="card-header bg-primary-dark">
            <h2 class="card-title">
                {{ $gettext('System Logs') }}
            </h2>
        </div>

        <log-list :url="systemLogsUrl" @view="viewLog"></log-list>
    </div>

    <div class="card" v-if="stationLogs.length > 0">
        <div class="card-header bg-primary-dark">
            <h2 class="card-title">
                {{ $gettext('Logs by Station') }}
            </h2>
        </div>

        <b-tabs pills lazy nav-class="card-header-pills" nav-wrapper-class="card-header">
            <b-tab v-for="row in stationLogs" :key="row.id" :title="row.name">
                <log-list :url="row.url" @view="viewLog"></log-list>
            </b-tab>
        </b-tabs>
    </div>

    <streaming-log-modal ref="modal"></streaming-log-modal>
</template>

<script setup>
import LogList from "~/components/Common/LogList";
import StreamingLogModal from "~/components/Common/StreamingLogModal";
import {ref} from "vue";

const props = defineProps({
    systemLogsUrl: String,
    stationLogs: Array
});

const modal = ref(); // StreamingLogModal

const viewLog = (url) => {
    modal.value.show(url);
};
</script>
