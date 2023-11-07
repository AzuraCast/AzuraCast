<template>
    <modal
        id="log_view_modal"
        ref="$modal"
        size="md"
        :title="$gettext('Log Viewer')"
    >
        <pre
            id="modal-log-view-contents"
            class="form-control log-viewer"
            style="height: 300px; overflow-y: auto;"
        >{{ lastOutput }}</pre>
    </modal>
</template>

<script setup lang="ts">
import {ref} from "vue";
import Modal from "~/components/Common/Modal.vue";
import {ModalTemplateRef, useHasModal} from "~/functions/useHasModal.ts";

const props = defineProps({
    lastOutput: {
        type: String,
        required: true
    },
});

const $modal = ref<ModalTemplateRef>(null);
const {show} = useHasModal($modal);

defineExpose({
    show
})
</script>
