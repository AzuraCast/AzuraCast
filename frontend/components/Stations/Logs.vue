<template>
    <div class="row row-of-cards">
        <div class="col-md-8">
            <section
                class="card"
                role="region"
                aria-labelledby="hdr_available_logs"
            >
                <div class="card-header text-bg-primary">
                    <h2
                        id="hdr_available_logs"
                        class="card-title"
                    >
                        {{ $gettext('Available Logs') }}
                    </h2>
                </div>

                <loading :loading="isLoading" lazy>
                    <log-list
                        v-if="data"
                        :logs="data"
                        @view="viewLog"
                    />
                </loading>
            </section>

            <streaming-log-modal ref="$modal" />
        </div>
        <div class="col-md-4">
            <section
                class="card"
                role="region"
                aria-labelledby="hdr_need_help"
            >
                <div class="card-header text-bg-primary">
                    <h2
                        id="hdr_need_help"
                        class="card-title"
                    >
                        {{ $gettext('Need Help?') }}
                    </h2>
                </div>
                <div class="card-body">
                    <p class="card-text">
                        {{ $gettext('You can find answers for many common questions in our support documents.') }}
                    </p>
                    <p class="card-text">
                        <a
                            href="/docs/help/troubleshooting/"
                            target="_blank"
                        >
                            {{ $gettext('Support Documents') }}
                        </a>
                    </p>
                    <p class="card-text">
                        {{
                            $gettext('If you\'re experiencing a bug or error, you can submit a GitHub issue using the link below.')
                        }}
                    </p>
                </div>
                <div class="card-body">
                    <a
                        class="btn btn-primary"
                        role="button"
                        href="https://github.com/AzuraCast/AzuraCast/issues/new/choose"
                        target="_blank"
                    >
                        <icon-ic-support/>

                        <span>
                            {{ $gettext('Add New GitHub Issue') }}
                        </span>
                    </a>
                </div>
            </section>
        </div>
    </div>
</template>

<script setup lang="ts">
import StreamingLogModal from "~/components/Common/StreamingLogModal.vue";
import LogList from "~/components/Common/LogList.vue";
import {useTemplateRef} from "vue";
import {QueryKeys, queryKeyWithStation} from "~/entities/Queries.ts";
import {useQuery} from "@tanstack/vue-query";
import {ApiLogType} from "~/entities/ApiInterfaces.ts";
import {useAxios} from "~/vendor/axios.ts";
import Loading from "~/components/Common/Loading.vue";
import IconIcSupport from "~icons/ic/baseline-support";
import {useApiRouter} from "~/functions/useApiRouter.ts";

const {getStationApiUrl} = useApiRouter();
const logsUrl = getStationApiUrl('/logs');

const {axios} = useAxios();

type ApiLogRow = Required<ApiLogType>

const {data, isLoading} = useQuery<ApiLogRow[]>({
    queryKey: queryKeyWithStation([
        QueryKeys.StationLogs
    ]),
    queryFn: async ({signal}) => {
        const {data} = await axios.get<ApiLogRow[]>(logsUrl.value, {signal});
        return data;
    },
    placeholderData: () => []
});

const $modal = useTemplateRef('$modal');

const viewLog = (url: string, isStreaming: boolean) => {
    $modal.value?.show(url, isStreaming);
};
</script>
