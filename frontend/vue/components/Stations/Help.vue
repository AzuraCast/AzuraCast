<template>
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-primary-dark">
                    <h2 class="card-title">
                        {{ $gettext('Available Logs') }}
                    </h2>
                </div>

                <log-list :url="logsUrl" @view="viewLog"></log-list>
            </div>

            <streaming-log-modal ref="modal"></streaming-log-modal>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-primary-dark">
                    <h2 class="card-title">
                        {{ $gettext('Need Help?') }}
                    </h2>
                </div>
                <div class="card-body">
                    <p class="card-text">
                        {{ $gettext('You can find answers for many common questions in our support documents.') }}
                    </p>
                    <p class="card-text">
                        <a href="https://docs.azuracast.com/en/user-guide/troubleshooting" target="_blank">
                            {{ $gettext('Support Documents') }}
                        </a>
                    </p>
                    <p class="card-text">
                        {{
                            $gettext('If you\'re experiencing a bug or error, you can submit a GitHub issue using the link below.')
                        }}
                    </p>
                </div>
                <div class="card-actions">
                    <a class="btn btn-outline-primary" role="button"
                       href="https://github.com/AzuraCast/AzuraCast/issues/new/choose" target="_blank">
                        <icon icon="contact_support"></icon>
                        {{ $gettext('Add New GitHub Issue') }}
                    </a>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import Icon from "~/components/Common/Icon";
import StreamingLogModal from "~/components/Common/StreamingLogModal";
import LogList from "~/components/Common/LogList";
import {ref} from "vue";

const props = defineProps({
    logsUrl: String,
});

const modal = ref(); // BModal

const viewLog = (url) => {
    modal.value?.show(url);
};
</script>
