<template>
    <div class="row-of-cards">
        <section
            class="card"
            role="region"
            aria-labelledby="hdr_system_logs"
        >
            <div class="card-header text-bg-primary">
                <h2
                    id="hdr_system_logs"
                    class="card-title"
                >
                    {{ $gettext('System Logs') }}
                </h2>
            </div>

            <log-list
                :url="systemLogsUrl"
                @view="viewLog"
            />
        </section>

        <section
            v-if="stationLogs.length > 0"
            class="card"
            role="region"
            aria-labelledby="hdr_logs_by_station"
        >
            <div class="card-header text-bg-primary">
                <h2
                    id="hdr_logs_by_station"
                    class="card-title"
                >
                    {{ $gettext('Logs by Station') }}
                </h2>
            </div>

            <b-tabs
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
        </section>
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
