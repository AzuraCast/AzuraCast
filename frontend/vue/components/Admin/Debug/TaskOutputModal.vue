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
import {ref, Ref} from "vue";
import TaskOutput from "~/components/Admin/Debug/TaskOutput.vue";

const $modal: Ref<InstanceType<typeof Modal> | null> = ref(null);

const logOutput: Ref<Array<object>> = ref([]);

const open = (newLogOutput: Array<object>) => {
    logOutput.value = newLogOutput;
    $modal.value?.show();
}

defineExpose({
    open
});
</script>
