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
            <form-basic-info v-model:form="form" />
            <form-schedule v-model:schedule-items="form.schedule_items" />
            <form-advanced
                v-if="enableAdvancedFeatures"
                v-model:form="form"
            />
        </tabs>
    </modal-form>
</template>

<script setup lang="ts">
import FormBasicInfo from "~/components/Stations/Playlists/Form/BasicInfo.vue";
import FormSchedule from "~/components/Stations/Playlists/Form/Schedule.vue";
import FormAdvanced from "~/components/Stations/Playlists/Form/Advanced.vue";
import {BaseEditModalEmits, BaseEditModalProps, useBaseEditModal} from "~/functions/useBaseEditModal";
import {computed, useTemplateRef} from "vue";
import {useTranslate} from "~/vendor/gettext";
import {useNotify} from "~/functions/useNotify";
import ModalForm from "~/components/Common/ModalForm.vue";
import {useAzuraCast} from "~/vendor/azuracast";
import Tabs from "~/components/Common/Tabs.vue";

const props = defineProps<BaseEditModalProps>();

const emit = defineEmits<BaseEditModalEmits & {
    (e: 'needs-restart'): void
}>();

const {enableAdvancedFeatures} = useAzuraCast();

const $modal = useTemplateRef('$modal');

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
    {
        schedule_items: {}
    },
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
