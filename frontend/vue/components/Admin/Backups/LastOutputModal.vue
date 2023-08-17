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

<script setup>
import {ref} from "vue";
import Modal from "~/components/Common/Modal.vue";

const props = defineProps({
    lastOutput: {
        type: String,
        required: true
    },
});

const $modal = ref(); // Template ref
const show = () => {
    $modal.value.show();
};

defineExpose({
    show
})
</script>
