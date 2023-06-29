<template>
    <o-modal
        ref="$modal"
        v-model:active="isActive"
        :aria-label="title"
    >
        <o-loading :active="busy">
            <div
                id="exampleModalToggle"
                class="modal fade"
                aria-hidden="true"
                aria-labelledby="exampleModalToggleLabel"
                tabindex="-1"
            >
                <div
                    class="modal-dialog modal-dialog-centered"
                    :class="'modal_'+size"
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
                                @click.prevent="close"
                            />
                        </div>
                        <div class="modal-body">
                            <slot />
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

const show = () => {
    isActive.value = true;
};
const hide = () => {
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
    show,
    hide
});
</script>
