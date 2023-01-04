<template>
    <b-modal
        id="station_edit_modal"
        ref="$modal"
        size="lg"
        :title="langTitle"
        :busy="loading"
        @shown="resetForm"
        @hidden="clearContents"
    >
        <admin-stations-form
            v-bind="$props"
            ref="$form"
            is-modal
            :create-url="createUrl"
            :edit-url="editUrl"
            :is-edit-mode="isEditMode"
            @error="close"
            @submitted="onSubmit"
            @valid-update="onValidUpdate"
            @loading-update="onLoadingUpdate"
        >
            <template #submitButton>
                <invisible-submit-button />
            </template>
        </admin-stations-form>

        <template #modal-footer>
            <b-button
                variant="default"
                type="button"
                @click="close"
            >
                {{ $gettext('Close') }}
            </b-button>
            <b-button
                :variant="(disableSaveButton) ? 'danger' : 'primary'"
                type="submit"
                @click="doSubmit"
            >
                {{ $gettext('Save Changes') }}
            </b-button>
        </template>
    </b-modal>
</template>

<script setup>
import AdminStationsForm from "~/components/Admin/Stations/StationForm.vue";
import InvisibleSubmitButton from "~/components/Common/InvisibleSubmitButton.vue";
import {computed, ref} from "vue";
import {useTranslate} from "~/vendor/gettext";
import {BModal} from "bootstrap-vue";
import stationFormProps from "~/components/Admin/Stations/stationFormProps";

const props = defineProps({
    ...stationFormProps,
    createUrl: {
        type: String,
        required: true
    }
});

const emit = defineEmits(['relist']);

const editUrl = ref(null);
const loading = ref(true);
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

const $modal = ref(); // BModal

const onValidUpdate = (newValue) => {
    disableSaveButton.value = !newValue;
};

const onLoadingUpdate = (newValue) => {
    loading.value = newValue;
};

const create = () => {
    editUrl.value = null;
    $modal.value.show();
};

const edit = (recordUrl) => {
    editUrl.value = recordUrl;
    $modal.value.show();
};

const $form = ref(); // AdminStationsForm

const resetForm = () => {
    $form.value.reset();
};

const close = () => {
    $modal.value.hide();
};

const onSubmit = () => {
    emit('relist');
    close();
};

const doSubmit = () => {
    $form.value.submit();
};

const clearContents = () => {
    editUrl.value = null;
};

defineExpose({
    create,
    edit
});
</script>
