<template>
    <modal
        id="logs_modal"
        ref="$modal"
        size="xl"
        :title="$gettext('Log Viewer')"
        no-enforce-focus
        @hidden="clearContents"
    >
        <template v-if="logUrl">
            <streaming-log-view
                v-if="isStreaming"
                ref="$logView"
                :log-url="logUrl"
            />
            <fixed-log-view
                v-else
                ref="$logView"
                :log-url="logUrl"
            />
        </template>

        <template #modal-footer>
            <button
                class="btn btn-secondary"
                type="button"
                @click="close"
            >
                {{ $gettext('Close') }}
            </button>
            <button
                class="btn btn-primary btn_copy"
                type="button"
                @click.prevent="doCopy"
            >
                {{ $gettext('Copy to Clipboard') }}
            </button>
        </template>
    </modal>
</template>

<script setup>
import StreamingLogView from "~/components/Common/StreamingLogView";
import {ref} from "vue";
import {useClipboard} from "@vueuse/core";
import Modal from "~/components/Common/Modal.vue";
import FixedLogView from "~/components/Common/FixedLogView.vue";

const logUrl = ref('');
const isStreaming = ref(true);

const $modal = ref(); // Template ref
const $logView = ref(); // Template ref

const show = (newLogUrl, newIsStreaming = true) => {
    logUrl.value = newLogUrl;
    isStreaming.value = newIsStreaming;

    $modal.value.show();
};

const clipboard = useClipboard();

const doCopy = () => {
    clipboard.copy($logView.value.getContents());
};

const close = () => {
    $modal.value.hide();
}

const clearContents = () => {
    logUrl.value = '';
}

defineExpose({
    show
})
</script>
