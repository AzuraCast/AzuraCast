<template>
    <b-modal id="logs_modal" ref="modal" :title="$gettext('Log Viewer')">
        <textarea class="form-control log-viewer" spellcheck="false" readonly>{{ logs }}</textarea>

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
import {ref} from "vue";
import {get, set, templateRef, useClipboard} from "@vueuse/core";

const logs = ref('Loading...');
const $modal = templateRef('modal');

const show = (newLogs) => {
    let logDisplay = [];
    newLogs.forEach((log) => {
        logDisplay.push(log.formatted);
    });

    set(logs, logDisplay.join(''));
    get($modal).show();
};

const clipboard = useClipboard();

const doCopy = () => {
    clipboard.copy(get(logs));
};

const close = () => {
    get($modal).hide();
}

defineExpose({
    show
});
</script>
