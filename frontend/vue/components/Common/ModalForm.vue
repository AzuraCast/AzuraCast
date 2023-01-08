<template>
    <b-modal
        :id="id"
        ref="$modal"
        :size="size"
        :centered="centered"
        :title="title"
        :busy="loading"
        :no-enforce-focus="noEnforceFocus"
        @shown="onShown"
        @hidden="onHidden"
    >
        <template #default="slotProps">
            <b-overlay
                variant="card"
                :show="loading"
            >
                <b-alert
                    variant="danger"
                    :show="error != null"
                >
                    {{ error }}
                </b-alert>

                <b-form
                    class="form vue-form"
                    @submit.prevent="doSubmit"
                >
                    <slot
                        name="default"
                        v-bind="slotProps"
                    />

                    <invisible-submit-button />
                </b-form>
            </b-overlay>
        </template>

        <template #modal-footer="slotProps">
            <slot
                name="modal-footer"
                v-bind="slotProps"
            >
                <b-button
                    variant="default"
                    type="button"
                    @click="hide"
                >
                    {{ $gettext('Close') }}
                </b-button>
                <b-button
                    :variant="(disableSaveButton) ? 'danger' : 'primary'"
                    type="submit"
                    @click="doSubmit"
                >
                    <slot name="save-button-name">
                        {{ $gettext('Save Changes') }}
                    </slot>
                </b-button>
            </slot>
        </template>

        <template
            v-for="(_, slot) of useSlotsExcept($slots, ['default', 'modal-footer'])"
            #[slot]="scope"
        >
            <slot
                :name="slot"
                v-bind="scope"
            />
        </template>
    </b-modal>
</template>

<script setup>
import InvisibleSubmitButton from "~/components/Common/InvisibleSubmitButton.vue";
import {ref} from "vue";
import useSlotsExcept from "~/functions/useSlotsExcept";

const props = defineProps({
    title: {
        type: String,
        required: true
    },
    size: {
        type: String,
        default: 'lg'
    },
    centered: {
        type: Boolean,
        default: false
    },
    id: {
        type: String,
        default: 'edit-modal'
    },
    loading: {
        type: Boolean,
        default: false
    },
    disableSaveButton: {
        type: Boolean,
        default: false
    },
    noEnforceFocus: {
        type: Boolean,
        default: false,
    },
    error: {
        type: String,
        default: null
    }
});

const emit = defineEmits(['submit', 'shown', 'hidden']);

const doSubmit = () => {
    emit('submit');
};

const onShown = () => {
    emit('shown');
};

const onHidden = () => {
    emit('hidden');
};

const $modal = ref(); // Template Ref

const hide = () => {
    $modal.value.hide();
};

const show = () => {
    $modal.value.show();
};

defineExpose({
    show,
    hide
});
</script>
