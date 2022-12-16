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
import {get, set, templateRef, useClipboard} from "@vueuse/core";

const logUrl = ref('');
const $modal = templateRef('modal');
const $logView = templateRef('logView');

const show = (newLogUrl) => {
    set(logUrl, newLogUrl);
    get($modal).show();
};

const clipboard = useClipboard();

const doCopy = () => {
    clipboard.copy(get($logView).getContents());
};

const close = () => {
    get($modal).hide();
}

const clearContents = () => {
    set(logUrl, '');
}

defineExpose({
    show
})
</script>
