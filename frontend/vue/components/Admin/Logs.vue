<template>
    <div>
        <div class="card mb-3">
            <div class="card-header bg-primary-dark">
                <h2 class="card-title">
                    <translate key="hdr_system_logs">System Logs</translate>
                </h2>
            </div>

            <log-list :url="systemLogsUrl" @view="viewLog"></log-list>
        </div>

        <div class="card" v-if="stationLogs.length > 0">
            <div class="card-header bg-primary-dark">
                <h2 class="card-title">
                    <translate key="hdr_logs_by_station">Logs by Station</translate>
                </h2>
            </div>

            <b-tabs pills lazy nav-class="card-header-pills" nav-wrapper-class="card-header">
                <b-tab v-for="row in stationLogs" :key="row.id" :title="row.name">
                    <log-list :url="row.url" @view="viewLog"></log-list>
                </b-tab>
            </b-tabs>
        </div>

        <streaming-log-modal ref="modal"></streaming-log-modal>
    </div>
</template>

<script>
import LogList from "~/components/Common/LogList";
import StreamingLogModal from "~/components/Common/StreamingLogModal";

export default {
    name: 'AdminLogs',
    components: {StreamingLogModal, LogList},
    props: {
        systemLogsUrl: String,
        stationLogs: Array
    },
    methods: {
        viewLog(url) {
            this.$refs.modal.show(url);
        }
    }
}
</script>
