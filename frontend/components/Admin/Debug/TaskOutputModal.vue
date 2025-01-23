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
import {ref, Ref, useTemplateRef} from "vue";
import TaskOutput from "~/components/Admin/Debug/TaskOutput.vue";
import {useHasModal} from "~/functions/useHasModal.ts";

const $modal = useTemplateRef('$modal');
const {show} = useHasModal($modal);

const logOutput: Ref<Array<object>> = ref([]);

const open = (newLogOutput: Array<object>) => {
    logOutput.value = newLogOutput;
    show();
}

defineExpose({
    open
});
</script>
