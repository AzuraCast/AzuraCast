<template>
    <b-modal size="md" id="log_view_modal" ref="modal" :title="$gettext('Log Viewer')">
        <pre id="modal-log-view-contents" class="form-control log-viewer"
             style="height: 300px; overflow-y: auto;">{{ lastOutput }}</pre>
    </b-modal>
</template>

<script setup>
import {get, templateRef} from "@vueuse/core";

const props = defineProps({
    lastOutput: String,
});

const $modal = templateRef('modal');
const show = () => {
    get($modal).show();
};

defineExpose({
    show
})
</script>
