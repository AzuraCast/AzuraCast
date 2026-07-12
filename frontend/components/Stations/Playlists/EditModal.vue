<template>
    <modal-form
        ref="$modal"
        :loading="loading"
        :title="langTitle"
        :error="error"
        :disable-save-button="r$.$invalid"
        @submit="doSubmit"
        @hidden="clearContents"
    >
        <tabs>
            <form-basic-info/>
            <form-schedule v-model:schedule-items="form.schedule_items" />
            <form-advanced v-if="![PlaylistSources.Playlists, PlaylistSources.Requests].includes(form.source)" />
        </tabs>
    </modal-form>
</template>

<script setup lang="ts">
import { storeToRefs } from "pinia";
import { computed, toRef, useTemplateRef } from "vue";
import ModalForm from "~/components/Common/ModalForm.vue";
import Tabs from "~/components/Common/Tabs.vue";
import { useNotify } from "~/components/Common/Toasts/useNotify.ts";
import FormAdvanced from "~/components/Stations/Playlists/Form/Advanced.vue";
import FormBasicInfo from "~/components/Stations/Playlists/Form/BasicInfo.vue";
import { useStationsPlaylistsForm } from "~/components/Stations/Playlists/Form/form.ts";
import FormSchedule from "~/components/Stations/Playlists/Form/Schedule.vue";
import mergeExisting from "~/functions/mergeExisting.ts";
import { PlaylistSources } from "~/entities/ApiInterfaces";
import {
    BaseEditModalEmits,
    BaseEditModalProps,
    useBaseEditModal,
} from "~/functions/useBaseEditModal";
import { useTranslate } from "~/vendor/gettext";
import { useAppCollectScope } from "~/vendor/regle.ts";

const props = defineProps<BaseEditModalProps>();

const emit = defineEmits<BaseEditModalEmits & ((e: "needs-restart") => void)>();

const $modal = useTemplateRef("$modal");

const { notifySuccess } = useNotify();

const formStore = useStationsPlaylistsForm();
const { form, r$ } = storeToRefs(formStore);
const { $reset: resetForm } = formStore;

const { r$: validatedr$ } = useAppCollectScope("stations-playlists");

const {
    loading,
    error,
    isEditMode,
    clearContents,
    create,
    edit,
    doSubmit,
    close,
} = useBaseEditModal(
    toRef(props, "createUrl"),
    emit,
    $modal,
    resetForm,
    (data) => {
        r$.value.$reset({
            toState: mergeExisting(r$.value.$value, data),
        });
    },
    async () => {
        const { valid } = await validatedr$.$validate();
        return { valid, data: form.value };
    },
    {
        onSubmitSuccess: () => {
            notifySuccess();
            emit("relist");
            emit("needs-restart");
            close();
        },
    },
);

const { $gettext } = useTranslate();

const langTitle = computed(() => {
    return isEditMode.value
        ? $gettext("Edit Playlist")
        : $gettext("Add Playlist");
});

defineExpose({
    create,
    edit,
    close,
});
</script>
