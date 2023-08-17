<template>
    <Teleport to="body">
        <div
            v-bind="$attrs"
            ref="$modal"
            class="modal fade"
            tabindex="-1"
            :aria-label="title"
            :class="'modal-'+size"
            aria-hidden="true"
        >
            <div class="modal-dialog">
                <div
                    v-if="isActive"
                    class="modal-content"
                >
                    <div
                        v-if="slots['modal-header'] || title"
                        class="modal-header"
                    >
                        <h1
                            v-if="title"
                            class="modal-title fs-5"
                        >
                            {{ title }}
                        </h1>
                        <slot name="modal-header" />
                        <button
                            type="button"
                            class="btn-close"
                            :aria-label="$gettext('Close')"
                            @click.prevent="hide"
                        />
                    </div>
                    <div class="modal-body">
                        <loading :loading="busy">
                            <slot name="default" />
                        </loading>
                    </div>
                    <div
                        v-if="slots['modal-footer']"
                        class="modal-footer"
                    >
                        <slot name="modal-footer" />
                    </div>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<script setup>
import Modal from 'bootstrap/js/src/modal';
import {onMounted, ref, useSlots, watch} from 'vue';
import Loading from "~/components/Common/Loading.vue";
import {useEventListener} from "@vueuse/core";

const slots = useSlots();

const props = defineProps({
    active: {
        type: Boolean,
        default: false
    },
    busy: {
        type: Boolean,
        default: false
    },
    size: {
        type: String,
        default: 'md'
    },
    title: {
        type: String,
        default: null
    }
});

const emit = defineEmits([
    'shown',
    'hidden',
    'update:active'
]);

const isActive = ref(props.active);
watch(isActive, (newActive) => {
    emit('update:active', newActive);
});

let bsModal = null;
const $modal = ref(); // div class="modal"

onMounted(() => {
    bsModal = new Modal($modal.value);
});

useEventListener(
    $modal,
    'hide.bs.modal',
    () => {
        isActive.value = false;
    }
);

useEventListener(
    $modal,
    'show.bs.modal',
    () => {
        isActive.value = true;
    }
);

useEventListener(
    $modal,
    'hidden.bs.modal',
    () => {
        emit('hidden');
    }
);

useEventListener(
    $modal,
    'shown.bs.modal',
    () => {
        emit('shown');
    }
);

const show = () => {
    bsModal?.show();
};

const hide = () => {
    bsModal?.hide();
};

defineExpose({
    show,
    hide
});
</script>
