<template>
    <b-modal id="logs_modal" size="lg" ref="modal" @hidden="clearContents"
             :title="$gettext('Log Viewer')" no-enforce-focus>
        <streaming-log-view ref="logView" :log-url="logUrl"></streaming-log-view>

        <template #modal-footer>
            <b-button variant="default" type="button" @click="close">
                {{ $gettext('Close') }}
            </b-button>
            <b-button variant="primary" class="btn_copy" @click.prevent="doCopy" type="button">
                {{ $gettext('Copy to Clipboard') }}
            </b-button>
        </template>
    </b-modal>
</template>

<script setup>
import StreamingLogView from "~/components/Common/StreamingLogView";
import {ref} from "vue";
import {useClipboard} from "@vueuse/core";

const logUrl = ref('');
const modal = ref(); // Template ref
const logView = ref(); // Template ref

const show = (newLogUrl) => {
    logUrl.value = newLogUrl;
    modal.value.show();
};

const clipboard = useClipboard();

const doCopy = () => {
    clipboard.copy(logView.value.getContents());
};

const close = () => {
    modal.value.hide();
}

const clearContents = () => {
    logUrl.value = '';
}

defineExpose({
    show
})
</script>
