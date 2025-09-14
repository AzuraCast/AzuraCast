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
            v-on="eventListeners"
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
                    <div
                        v-if="slots['default']"
                        class="modal-body"
                    >
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

<script setup lang="ts">
import {Modal} from "bootstrap";
import {onMounted, onUnmounted, ref, useSlots, useTemplateRef, watch} from "vue";
import Loading from "~/components/Common/Loading.vue";

const slots = useSlots();

const props = withDefaults(
    defineProps<{
        active?: boolean,
        busy?: boolean,
        size?: string,
        title?: string,
    }>(),
    {
        active: false,
        busy: false,
        size: 'md'
    }
);

const emit = defineEmits<{
    (e: 'shown'): void,
    (e: 'hidden'): void,
    (e: 'update:active', active: boolean): void
}>();

const isActive = ref<boolean>(props.active);
watch(isActive, (newActive) => {
    emit('update:active', newActive);
});

let bsModal: Modal | null = null;
const $modal = useTemplateRef('$modal');

onMounted(() => {
    if ($modal.value) {
        bsModal = new Modal($modal.value);
    }
});

onUnmounted(() => {
    bsModal?.dispose();
});

const eventListeners = {
    ['hide.bs.modal']: () => {
        isActive.value = false;
    },
    ['hidden.bs.modal']: () => {
        emit('hidden');
    },
    ['show.bs.modal']: () => {
        isActive.value = true;
    },
    ['shown.bs.modal']: () => {
        emit('shown');
    },
};

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
