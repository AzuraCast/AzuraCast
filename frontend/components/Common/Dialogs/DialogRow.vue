<template>
    <div
        v-bind="$attrs"
        ref="$modal"
        class="modal modal-md fade"
        tabindex="-1"
        :aria-label="title"
        aria-hidden="true"
        role="dialog"
        v-on="eventListeners"
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

<script setup lang="ts">
import {Modal} from "bootstrap";
import {onMounted, useTemplateRef} from "vue";
import {DialogOptions, useDialog} from "./useDialog.ts";

type DialogComponentProps = DialogOptions & {
    id: string
}

const props = withDefaults(defineProps<DialogComponentProps>(), {
    confirmButtonClass: 'btn-primary',
    cancelButtonClass: 'btn-secondary',
    focusCancel: false
});

const {resolveDialog, removeDialog} = useDialog();

let bsModal: Modal | null = null;
const $modal = useTemplateRef('$modal');
const $cancelButton = useTemplateRef('$cancelButton');
const $confirmButton = useTemplateRef('$confirmButton');

const eventListeners = {
    ['hide.bs.modal']: () => {
        resolveDialog(
            props.id,
            {
                value: false
            }
        );
    },
    ['hidden.bs.modal']: () => {
        bsModal?.dispose();
        removeDialog(props.id);
    },
    ['shown.bs.modal']: () => {
        if (props.focusCancel) {
            $cancelButton.value?.focus();
        } else {
            $confirmButton.value?.focus();
        }
    },
};

const onButtonClick = (value: boolean) => {
    resolveDialog(
        props.id,
        {
            value
        }
    );

    bsModal?.hide();
};

onMounted(() => {
    bsModal = new Modal($modal.value!);
    bsModal.show();
});
</script>
