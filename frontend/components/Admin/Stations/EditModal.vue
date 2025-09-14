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
            v-bind="props"
            ref="$form"
            is-modal
            :create-url="createUrl"
            :edit-url="editUrl"
            :is-edit-mode="isEditMode"
            @error="hide"
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
import {computed, ref, useTemplateRef} from "vue";
import {useTranslate} from "~/vendor/gettext";
import Modal from "~/components/Common/Modal.vue";
import {useHasModal} from "~/functions/useHasModal.ts";
import {HasRelistEmit} from "~/functions/useBaseEditModal.ts";
import {ApiAdminVueStationsFormProps} from "~/entities/ApiInterfaces.ts";

defineOptions({
    inheritAttrs: false
});

interface StationEditModalProps extends ApiAdminVueStationsFormProps {
    createUrl: string
}

const props = defineProps<StationEditModalProps>();

const emit = defineEmits<HasRelistEmit>();

const editUrl = ref<string | null>(null);
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

const $modal = useTemplateRef('$modal');
const {show, hide} = useHasModal($modal);

const onValidUpdate = (newValue: boolean) => {
    disableSaveButton.value = !newValue;
};

const create = () => {
    editUrl.value = null;
    show();
};

const edit = (recordUrl: string) => {
    editUrl.value = recordUrl;
    show();
};

const $form = useTemplateRef('$form');

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
