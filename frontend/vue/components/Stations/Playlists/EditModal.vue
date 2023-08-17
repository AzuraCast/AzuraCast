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
        <o-tabs
            nav-tabs-class="nav-tabs"
            content-class="mt-3"
        >
            <form-basic-info v-model:form="form" />
            <form-schedule v-model:schedule-items="form.schedule_items" />
            <form-advanced
                v-if="enableAdvancedFeatures"
                v-model:form="form"
            />
        </o-tabs>
    </modal-form>
</template>

<script setup>
import FormBasicInfo from './Form/BasicInfo';
import FormSchedule from './Form/Schedule';
import FormAdvanced from './Form/Advanced';
import {baseEditModalProps, useBaseEditModal} from "~/functions/useBaseEditModal";
import {computed, ref} from "vue";
import {useTranslate} from "~/vendor/gettext";
import {useNotify} from "~/functions/useNotify";
import ModalForm from "~/components/Common/ModalForm.vue";
import {useAzuraCast} from "~/vendor/azuracast";

const props = defineProps({
    ...baseEditModalProps
});

const {enableAdvancedFeatures} = useAzuraCast();

const emit = defineEmits(['relist', 'needs-restart']);

const $modal = ref(); // Template Ref

const {notifySuccess} = useNotify();

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
        schedule_items: []
    },
    {
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
        ? $gettext('Edit Playlist')
        : $gettext('Add Playlist');
});

defineExpose({
    create,
    edit,
    close
});
</script>
