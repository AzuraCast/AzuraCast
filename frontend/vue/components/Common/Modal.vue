<template>
    <o-modal
        ref="$modal"
        v-model:active="isActiveLocal"
        :aria-label="title"
        :content-class="'modal-'+size"
        :width="null"
    >
        <div class="modal-content">
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
                <b-overlay :show="busy">
                    <slot name="default" />
                </b-overlay>
            </div>
            <div
                v-if="slots['modal-footer']"
                class="modal-footer"
            >
                <slot name="modal-footer" />
            </div>
        </div>
    </o-modal>
</template>

<script setup>
import {ref, useSlots, watch} from 'vue';
import {syncRef, useVModel} from "@vueuse/core";

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

const isActiveProp = useVModel(props, 'active', emit);
const isActiveLocal = ref(isActiveProp.value);

const show = () => {
    isActiveLocal.value = true;
};
const hide = () => {
    isActiveLocal.value = false;
};

syncRef(isActiveProp, isActiveLocal);

watch(isActiveLocal, (value) => {
    if (value) {
        emit('shown');
    } else {
        emit('hidden');
    }
});

defineExpose({
    show,
    hide
});
</script>
