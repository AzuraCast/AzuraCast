<template>
    <b-modal size="lg" id="station_edit_modal" ref="modal" :title="langTitle" :busy="loading"
             @shown="resetForm" @hidden="clearContents">
        <admin-stations-form v-bind="$props" ref="form" is-modal :create-url="createUrl" :edit-url="editUrl"
                             :is-edit-mode="isEditMode" @error="close" @submitted="onSubmit"
                             @validUpdate="onValidUpdate" @loadingUpdate="onLoadingUpdate">
            <template #submitButton>
                <invisible-submit-button></invisible-submit-button>
            </template>
        </admin-stations-form>

        <template #modal-footer>
            <b-button variant="default" type="button" @click="close">
                {{ $gettext('Close') }}
            </b-button>
            <b-button :variant="(disableSaveButton) ? 'danger' : 'primary'" type="submit" @click="doSubmit">
                {{ $gettext('Save Changes') }}
            </b-button>
        </template>
    </b-modal>
</template>

<script setup>
import AdminStationsForm, {StationFormProps} from "~/components/Admin/Stations/StationForm";
import InvisibleSubmitButton from "~/components/Common/InvisibleSubmitButton";
import {computed, ref} from "vue";
import gettext from "~/vendor/gettext";

const props = defineProps({
    ...StationFormProps.props,
    createUrl: String
});

const emit = defineEmits(['relist']);

const editUrl = ref(null);
const loading = ref(true);
const disableSaveButton = ref(true);

const isEditMode = computed(() => {
    return editUrl.value !== null;
});

const {$gettext} = gettext;

const langTitle = computed(() => {
    return isEditMode.value
        ? $gettext('Edit Station')
        : $gettext('Add Station');
});

const modal = ref(); // BVModal

const onValidUpdate = (newValue) => {
    disableSaveButton.value = !newValue;
};

const onLoadingUpdate = (newValue) => {
    loading.value = newValue;
};

const create = () => {
    editUrl.value = null;
    modal.value.show();
};

const edit = (recordUrl) => {
    editUrl.value = recordUrl;
    modal.value.show();
};

const form = ref(); // Template Ref

const resetForm = () => {
    form.value.reset();
};

const close = () => {
    modal.value.hide();
};

const onSubmit = () => {
    emit('relist');
    close();
};

const doSubmit = () => {
    form.value.submit();
};

const clearContents = () => {
    editUrl.value = null;
};

defineExpose({
    create,
    edit
});
</script>

<script>
export default {
    inheritAttrs: false,
};
</script>
