<template>
    <modal
        ref="$modal"
        :title="$gettext('Log Output')"
        size="lg"
    >
        <div style="max-height: 300px; overflow-y: scroll">
            <task-output :logs="logOutput" />
        </div>
    </modal>
</template>
<script setup lang="ts">
import Modal from "~/components/Common/Modal.vue";
import {ref, useTemplateRef} from "vue";
import TaskOutput from "~/components/Admin/Debug/TaskOutput.vue";
import {useHasModal} from "~/functions/useHasModal.ts";
import {ApiAdminDebugLogEntry} from "~/entities/ApiInterfaces.ts";

const $modal = useTemplateRef('$modal');
const {show} = useHasModal($modal);

const logOutput = ref<ApiAdminDebugLogEntry[]>([]);

const open = (newLogOutput: ApiAdminDebugLogEntry[]) => {
    logOutput.value = newLogOutput;
    show();
}

defineExpose({
    open
});
</script>
