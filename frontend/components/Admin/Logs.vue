<template>
    <div class="row-of-cards">
        <card-page
            header-id="hdr_system_logs"
            :title="$gettext('System Logs')"
        >
            <loading :loading="isLoading" lazy>
                <log-list
                    v-if="data"
                    :logs="data.globalLogs"
                    @view="viewLog"
                />
            </loading>
        </card-page>

        <card-page
            v-if="data && data.stationLogs.length > 0"
            header-id="hdr_logs_by_station"
            :title="$gettext('Logs by Station')"
        >
            <div class="card-body">
                <tabs content-class="mt-3">
                    <tab
                        v-for="row in data.stationLogs"
                        :key="row.id"
                        :label="row.name"
                    >
                        <div class="card-body-flush">
                            <log-list
                                :logs="row.logs"
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
import {useAxios} from "~/vendor/axios.ts";
import {useQuery} from "@tanstack/vue-query";
import Loading from "~/components/Common/Loading.vue";
import {LogListRequired} from "~/entities/AdminLogs.ts";
import {useApiRouter} from "~/functions/useApiRouter.ts";

const {axios} = useAxios();

const {getApiUrl} = useApiRouter();
const systemLogsUrl = getApiUrl('/admin/logs');

const {data, isLoading} = useQuery<LogListRequired>({
    queryKey: [QueryKeys.AdminDebug, 'logs'],
    queryFn: async ({signal}) => {
        const {data} = await axios.get<LogListRequired>(systemLogsUrl.value, {signal});
        return data;
    },
    placeholderData: () => ({
        globalLogs: [],
        stationLogs: []
    })
});

const $modal = useTemplateRef('$modal');

const viewLog = (url: string, isStreaming: boolean) => {
    $modal.value?.show(url, isStreaming);
};
</script>
