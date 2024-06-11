<template>
    <modal-form
        ref="$modal"
        :loading="loading"
        :title="langTitle"
        :error="error"
        :disable-save-button="v$.$invalid"
        @submit="doSubmit"
        @hidden="clearContents"
    >
        <tabs>
            <form-basic-info
                v-model:form="form"
                :is-edit-mode="isEditMode"
            />
            <form-schedule
                v-model:schedule-items="form.schedule_items"
                :form="v$"
            />
            <form-artwork
                v-model="form.artwork_file"
                :artwork-src="record.links.art"
                :new-art-url="newArtUrl"
                :edit-art-url="record.links.art"
            />
        </tabs>
    </modal-form>
</template>

<script setup lang="ts">
import FormBasicInfo from './Form/BasicInfo.vue';
import FormSchedule from './Form/Schedule.vue';
import FormArtwork from './Form/Artwork.vue';
import mergeExisting from "~/functions/mergeExisting";
import {baseEditModalProps, ModalFormTemplateRef, useBaseEditModal} from "~/functions/useBaseEditModal";
import {computed, ref} from "vue";
import {useTranslate} from "~/vendor/gettext";
import {useResettableRef} from "~/functions/useResettableRef";
import ModalForm from "~/components/Common/ModalForm.vue";
import Tabs from "~/components/Common/Tabs.vue";

const props = defineProps({
    ...baseEditModalProps,
    newArtUrl: {
        type: String,
        required: true
    },
});

const emit = defineEmits(['relist']);

const $modal = ref<ModalFormTemplateRef>(null);

const {record, reset} = useResettableRef({
    has_custom_art: false,
    links: {}
});

const {
    loading,
    error,
    isEditMode,
    form,
    v$,
    clearContents,
    create,
    edit,
    doSubmit,
    close
} = useBaseEditModal(
    props,
    emit,
    $modal,
    {},
    {
        schedule_items: [],
        artwork_file: null
    },
    {
        resetForm: (originalResetForm) => {
            originalResetForm();
            reset();
        },
        populateForm: (data, formRef) => {
            record.value = data;
            formRef.value = mergeExisting(formRef.value, data);
        },
    },
);

const {$gettext} = useTranslate();

const langTitle = computed(() => {
    return isEditMode.value
        ? $gettext('Edit Streamer')
        : $gettext('Add Streamer');
});

defineExpose({
    create,
    edit,
    close
});
</script>
