<template>
    <b-modal
        id="cpu_stats_help_modal"
        ref="$modal"
        size="lg"
        centered
        :title="$gettext('Memory Stats Help')"
    >
        <div class="mb-2">
            <h6>
                <b-badge
                    pill
                    variant="warning"
                >
&nbsp;&nbsp;
                </b-badge>&nbsp;
                {{ $gettext('Cached') }}:
                {{ $gettext('The amount of memory Linux is using for disk caching.') }}
            </h6>
            <div class="ml-4">
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
                <b-badge
                    pill
                    variant="primary"
                >
&nbsp;&nbsp;
                </b-badge>&nbsp;
                {{ $gettext('Used') }}:
                {{ $gettext('The current Memory usage excluding cached memory.') }}
            </h6>
        </div>

        <template #modal-footer>
            <slot name="modal-footer">
                <b-button
                    variant="default"
                    type="button"
                    @click="close"
                >
                    {{ $gettext('Close') }}
                </b-button>
            </slot>
        </template>
    </b-modal>
</template>

<script setup>
import {ref} from "vue";

const $modal = ref(); // BModal

const create = () => {
    $modal.value.show();
}
const close = () => {
    $modal.value.hide();
}

defineExpose({
    create,
    close
});
</script>
