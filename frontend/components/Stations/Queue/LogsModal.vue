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
                @click="hide"
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

<script setup lang="ts">
import {ref, useTemplateRef} from "vue";
import {useClipboard} from "@vueuse/core";
import Modal from "~/components/Common/Modal.vue";
import {useHasModal} from "~/functions/useHasModal.ts";

const logs = ref<string>('Loading...');

const $modal = useTemplateRef('$modal');
const {show: showModal, hide} = useHasModal($modal);

const show = (newLogs: string[]) => {
    logs.value = newLogs.slice().join('');
    showModal();
};

const clipboard = useClipboard();

const doCopy = () => {
    void clipboard.copy(logs.value);
};

defineExpose({
    show
});
</script>
