<template>
    <modal
        id="logs_modal"
        ref="$modal"
        size="lg"
        :title="$gettext('Log Viewer')"
        no-enforce-focus
        @hidden="clearContents"
    >
        <streaming-log-view
            ref="$logView"
            :log-url="logUrl"
        />

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

const logUrl = ref('');
const $modal = ref(); // Template ref
const $logView = ref(); // Template ref

const show = (newLogUrl) => {
    logUrl.value = newLogUrl;
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
