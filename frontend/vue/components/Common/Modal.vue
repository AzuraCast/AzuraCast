<template>
    <o-modal
        ref="$modal"
        v-model:active="isActive"
        :aria-label="title"
        :content-class="'modal-'+size"
        :width="null"
        destroy-on-hide
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
    </o-modal>
</template>

<script setup>
import {nextTick, ref, useSlots, watch} from 'vue';
import Loading from "~/components/Common/Loading.vue";

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

    if (newActive) {
        nextTick(() => {
            emit('shown');
        });
    } else {
        emit('hidden');
    }
});

const show = () => {
    isActive.value = true;
};

const hide = () => {
    isActive.value = false;
};

defineExpose({
    show,
    hide
});
</script>
