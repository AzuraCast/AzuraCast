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

                <log-list
                    :url="logsUrl"
                    @view="viewLog"
                />
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
                        <icon :icon="IconSupport" />
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
import Icon from "~/components/Common/Icon.vue";
import StreamingLogModal from "~/components/Common/StreamingLogModal.vue";
import LogList from "~/components/Common/LogList.vue";
import {ref} from "vue";
import {getStationApiUrl} from "~/router";
import {IconSupport} from "~/components/Common/icons.ts";

const logsUrl = getStationApiUrl('/logs');

const $modal = ref<InstanceType<typeof StreamingLogModal> | null>(null);

const viewLog = (url, isStreaming) => {
    $modal.value?.show(url, isStreaming);
};
</script>
