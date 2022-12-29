<template>
    <b-modal
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
    </b-modal>
</template>

<script setup>
import {ref} from "vue";

const props = defineProps({
    lastOutput: String,
});

const $modal = ref(); // Template ref
const show = () => {
    $modal.value.show();
};

defineExpose({
    show
})
</script>
