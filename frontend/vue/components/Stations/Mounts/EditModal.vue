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
            <mount-form-basic-info
                v-model:form="form"
                :station-frontend-type="stationFrontendType"
            />
            <mount-form-auto-dj
                v-model:form="form"
                :station-frontend-type="stationFrontendType"
            />
            <mount-form-intro
                v-model="form.intro_file"
                :record-has-intro="record.intro_path !== null"
                :new-intro-url="newIntroUrl"
                :edit-intro-url="record.links.intro"
            />
            <mount-form-advanced
                v-if="enableAdvancedFeatures"
                v-model:form="form"
                :station-frontend-type="stationFrontendType"
            />
        </tabs>
    </modal-form>
</template>

<script setup>
import MountFormBasicInfo from './Form/BasicInfo';
import MountFormAutoDj from './Form/AutoDj';
import MountFormAdvanced from './Form/Advanced';
import MountFormIntro from "./Form/Intro";
import mergeExisting from "~/functions/mergeExisting";
import {baseEditModalProps, useBaseEditModal} from "~/functions/useBaseEditModal";
import {computed, ref} from "vue";
import {useNotify} from "~/functions/useNotify";
import {useTranslate} from "~/vendor/gettext";
import {useResettableRef} from "~/functions/useResettableRef";
import ModalForm from "~/components/Common/ModalForm.vue";
import {useAzuraCast} from "~/vendor/azuracast";
import Tabs from "~/components/Common/Tabs.vue";

const props = defineProps({
    ...baseEditModalProps,
    stationFrontendType: {
        type: String,
        required: true
    },
    newIntroUrl: {
        type: String,
        required: true
    }
});

const {enableAdvancedFeatures} = useAzuraCast();

const emit = defineEmits(['relist', 'needs-restart']);

const $modal = ref(); // Template Ref

const {notifySuccess} = useNotify();

const {record, reset} = useResettableRef({
    intro_path: null,
    links: {
        intro: null
    }
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
        intro_file: null
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
        onSubmitSuccess: () => {
            notifySuccess();
            emit('relist');
            emit('needs-restart');
            close();
        },
    }
);

const {$gettext} = useTranslate();

const langTitle = computed(() => {
    return isEditMode.value
        ? $gettext('Edit Mount Point')
        : $gettext('Add Mount Point');
});

defineExpose({
    create,
    edit,
    close
});
</script>
