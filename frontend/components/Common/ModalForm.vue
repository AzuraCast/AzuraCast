<template>
    <modal
        :id="id"
        ref="$modal"
        :size="size"
        :title="title"
        :busy="loading"
        @shown="onShown"
        @hidden="onHidden"
    >
        <template #default="slotProps">
            <div
                v-if="error"
                class="alert alert-danger"
            >
                {{ error }}
            </div>

            <form
                class="form vue-form"
                @submit.prevent="doSubmit"
            >
                <slot
                    name="default"
                    v-bind="slotProps"
                />
                <invisible-submit-button />
            </form>
        </template>

        <template #modal-footer="slotProps">
            <slot
                name="modal-footer"
                v-bind="slotProps"
            >
                <button
                    class="btn btn-secondary"
                    type="button"
                    @click="hide"
                >
                    {{ $gettext('Close') }}
                </button>
                <button
                    class="btn"
                    :class="(disableSaveButton) ? 'btn-danger' : 'btn-primary'"
                    :disabled="loading"
                    type="submit"
                    @click="doSubmit"
                >
                    <slot name="save-button-name">
                        {{ $gettext('Save Changes') }}
                    </slot>
                </button>
            </slot>
        </template>

        <template
            v-for="(_, slot) of useSlotsExcept(['default', 'modal-footer'])"
            #[slot]="scope"
        >
            <slot
                :name="slot"
                v-bind="scope"
            />
        </template>
    </modal>
</template>

<script setup lang="ts">
import InvisibleSubmitButton from "~/components/Common/InvisibleSubmitButton.vue";
import {useTemplateRef} from "vue";
import useSlotsExcept from "~/functions/useSlotsExcept";
import Modal from "~/components/Common/Modal.vue";
import {useHasModal} from "~/functions/useHasModal.ts";

withDefaults(
    defineProps<{
        title: string,
        size?: string,
        centered?: boolean,
        id?: string,
        loading?: boolean,
        disableSaveButton?: boolean,
        noEnforceFocus?: boolean,
        error?: string | null,
    }>(),
    {
        size: 'lg',
        centered: false,
        id: 'edit-modal',
        loading: false,
        disableSaveButton: false,
        noEnforceFocus: false
    }
);

const emit = defineEmits<{
    (e: 'submit'): void,
    (e: 'shown'): void,
    (e: 'hidden'): void
}>();

const doSubmit = () => {
    emit('submit');
};

const onShown = () => {
    emit('shown');
};

const onHidden = () => {
    emit('hidden');
};

const $modal = useTemplateRef('$modal');
const {show, hide} = useHasModal($modal);

defineExpose({
    show,
    hide
});
</script>
