<template>
    <o-modal ref="$modal" v-model:active="isActive" :aria-label="title">
        <o-loading :active="busy">
            <div class="modal fade" id="exampleModalToggle" aria-hidden="true" aria-labelledby="exampleModalToggleLabel"
                 tabindex="-1">
                <div class="modal-dialog modal-dialog-centered" :class="'modal_'+size">
                    <div class="modal-content">
                        <div class="modal-header" v-if="slots['modal-header'] || title">
                            <h1 class="modal-title fs-5" v-if="title">
                                {{ title }}
                            </h1>
                            <slot name="modal-header"></slot>
                            <button type="button" class="btn-close" @click.prevent="close"
                                    :aria-label="$gettext('Close')"/>
                        </div>
                        <div class="modal-body">
                            <slot></slot>
                        </div>
                        <div class="modal-footer" v-if="slots['modal-footer']">
                            <slot name="modal-footer"></slot>
                        </div>
                    </div>
                </div>
            </div>
        </o-loading>
    </o-modal>
</template>

<script setup>
import {useSlots, watch} from 'vue';
import {useVModel} from "@vueuse/core";

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

const isActive = useVModel(props, 'active', emit);

const open = () => {
    isActive.value = true;
};
const close = () => {
    isActive.value = false;
};

watch(isActive, (value) => {
    if (value) {
        emit('shown');
    } else {
        emit('hidden');
    }
});

defineExpose({
    open,
    close
});
</script>
