<template>
    <modal
        id="cpu_stats_help_modal"
        ref="$modal"
        size="lg"
        centered
        :title="$gettext('Memory Stats Help')"
    >
        <div class="mb-2">
            <h6>
                <span class="badge text-bg-warning me-1">&nbsp;&nbsp;</span>

                {{ $gettext('Cached') }}:
                {{ $gettext('The amount of memory Linux is using for disk caching.') }}
            </h6>
            <div class="ms-4">
                <p>
                    {{
                        $gettext('This can make it look like your memory is low while it actually is not. Some monitoring solutions/panels include cached memory in their used memory statistics without indicating this.')
                    }}
                </p>
                <p>
                    {{
                        $gettext('Disk caching makes a system much faster and more responsive in general. It does not take memory away from applications in any way since it will automatically be released by the operating system when needed.')
                    }}
                </p>
            </div>
        </div>

        <div class="mb-2">
            <h6>
                <span class="badge text-bg-primary me-1">&nbsp;&nbsp;</span>
                
                {{ $gettext('Used') }}:
                {{ $gettext('The current Memory usage excluding cached memory.') }}
            </h6>
        </div>

        <template #modal-footer>
            <slot name="modal-footer">
                <button
                    type="button"
                    class="btn btn-secondary"
                    @click="close"
                >
                    {{ $gettext('Close') }}
                </button>
            </slot>
        </template>
    </modal>
</template>

<script setup lang="ts">
import {ref} from "vue";
import Modal from "~/components/Common/Modal.vue";
import {ModalTemplateRef, useHasModal} from "~/functions/useHasModal.ts";

const $modal = ref<ModalTemplateRef>(null);
const {show: create, hide: close} = useHasModal($modal);

defineExpose({
    create,
    close
});
</script>
