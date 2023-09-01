<template>
    <modal
        id="station_edit_modal"
        ref="$modal"
        size="lg"
        :title="langTitle"
        :busy="false"
        @shown="resetForm"
        @hidden="clearContents"
    >
        <admin-stations-form
            v-bind="pickProps(props, stationFormProps)"
            ref="$form"
            is-modal
            :create-url="createUrl"
            :edit-url="editUrl"
            :is-edit-mode="isEditMode"
            @error="close"
            @submitted="onSubmit"
            @valid-update="onValidUpdate"
        >
            <template #submitButton>
                <invisible-submit-button />
            </template>
        </admin-stations-form>

        <template #modal-footer>
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
                type="submit"
                @click="doSubmit"
            >
                {{ $gettext('Save Changes') }}
            </button>
        </template>
    </modal>
</template>

<script setup lang="ts">
import AdminStationsForm from "~/components/Admin/Stations/StationForm.vue";
import InvisibleSubmitButton from "~/components/Common/InvisibleSubmitButton.vue";
import {computed, ref} from "vue";
import {useTranslate} from "~/vendor/gettext";
import stationFormProps from "~/components/Admin/Stations/stationFormProps";
import {pickProps} from "~/functions/pickProps";
import Modal from "~/components/Common/Modal.vue";
import {ModalTemplateRef, useHasModal} from "~/functions/useHasModal.ts";

const props = defineProps({
    ...stationFormProps,
    createUrl: {
        type: String,
        required: true
    }
});

const emit = defineEmits(['relist']);

const editUrl = ref(null);
const disableSaveButton = ref(true);

const isEditMode = computed(() => {
    return editUrl.value !== null;
});

const {$gettext} = useTranslate();

const langTitle = computed(() => {
    return isEditMode.value
        ? $gettext('Edit Station')
        : $gettext('Add Station');
});

const $modal = ref<ModalTemplateRef>(null);
const {show, hide} = useHasModal($modal);

const onValidUpdate = (newValue) => {
    disableSaveButton.value = !newValue;
};

const create = () => {
    editUrl.value = null;
    show();
};

const edit = (recordUrl) => {
    editUrl.value = recordUrl;
    show();
};

const $form = ref<InstanceType<typeof AdminStationsForm> | null>(null);

const resetForm = () => {
    $form.value?.reset();
};

const onSubmit = () => {
    emit('relist');
    hide();
};

const doSubmit = () => {
    $form.value?.submit();
};

const clearContents = () => {
    editUrl.value = null;
};

defineExpose({
    create,
    edit
});
</script>
