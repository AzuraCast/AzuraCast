<template>
    <modal
        id="logs_modal"
        ref="$modal"
        size="xl"
        :title="$gettext('Log Viewer')"
    >
        <textarea
            class="form-control log-viewer"
            spellcheck="false"
            readonly
            :value="logs"
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
                class="btn btn-primary"
                type="button"
                @click.prevent="doCopy"
            >
                {{ $gettext('Copy to Clipboard') }}
            </button>
        </template>
    </modal>
</template>

<script setup>
import {ref} from "vue";
import {useClipboard} from "@vueuse/core";
import Modal from "~/components/Common/Modal.vue";

const logs = ref('Loading...');
const $modal = ref(); // Template Ref

const show = (newLogs) => {
    const logDisplay = [];
    newLogs.forEach((log) => {
        logDisplay.push(log);
    });

    logs.value = logDisplay.join('');
    $modal.value.show();
};

const clipboard = useClipboard();

const doCopy = () => {
    clipboard.copy(logs.value);
};

const close = () => {
    $modal.value.hide();
}

defineExpose({
    show
});
</script>
