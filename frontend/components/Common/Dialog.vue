<template>
    <div
        v-bind="$attrs"
        ref="$modal"
        class="modal modal-md fade"
        tabindex="-1"
        :aria-label="title"
        aria-hidden="true"
        v-if="isActive"
        role="dialog"
    >
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header justify-content-center pb-2 pt-4 border-bottom-0">
                    <h1
                        v-if="title"
                        class="modal-title fs-3"
                    >
                        {{ title }}
                    </h1>
                </div>
                <div class="modal-footer justify-content-center pt-2 pb-4 border-top-0">
                    <button
                        ref="$confirmButton"
                        class="btn"
                        :class="confirmButtonClass"
                        @click.prevent="onButtonClick(true)"
                    >
                        {{ confirmButtonText }}
                    </button>
                    <button
                        ref="$cancelButton"
                        class="btn"
                        :class="cancelButtonClass"
                        @click.prevent="onButtonClick(false)"
                    >
                        {{ cancelButtonText }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script lang="ts">
export interface DialogOptions {
    title: string,
    confirmButtonText: string,
    confirmButtonClass?: string,
    cancelButtonText: string,
    cancelButtonClass?: string,
    focusCancel?: boolean
}

export interface DialogResponse {
    value: boolean
}

export interface DialogComponentProps extends DialogOptions {
    resolvePromise(body: DialogResponse): void
}
</script>

<script setup lang="ts">
import Modal from 'bootstrap/js/src/modal';
import {onMounted, ref} from "vue";
import {useEventListener} from "@vueuse/core";

const props = withDefaults(defineProps<DialogComponentProps>(), {
    confirmButtonClass: 'btn-primary',
    cancelButtonClass: 'btn-secondary',
    focusCancel: false
});

const isActive = ref(true);

const sendResult = (value: boolean = true) => {
    props.resolvePromise({
        value: value
    });
}

let bsModal = null;
const $modal = ref<HTMLDivElement | null>(null);
const $cancelButton = ref<HTMLButtonElement | null>(null);
const $confirmButton = ref<HTMLButtonElement | null>(null);

useEventListener(
    $modal,
    'hide.bs.modal',
    () => {
        sendResult(false);
    }
);

useEventListener(
    $modal,
    'hidden.bs.modal',
    () => {
        bsModal?.dispose();
        isActive.value = false;
    }
);

useEventListener(
    $modal,
    'shown.bs.modal',
    () => {
        if (props.focusCancel) {
            $cancelButton.value?.focus();
        } else {
            $confirmButton.value?.focus();
        }
    }
);

const onButtonClick = (value: boolean) => {
    sendResult(value);
    bsModal?.hide();
};

onMounted(() => {
    bsModal = new Modal($modal.value);
    bsModal.show();
});
</script>
