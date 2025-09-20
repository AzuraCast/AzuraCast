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
            <form-schedule v-model:schedule-items="form.schedule_items"/>
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
import FormBasicInfo from "~/components/Stations/Streamers/Form/BasicInfo.vue";
import FormSchedule from "~/components/Stations/Streamers/Form/Schedule.vue";
import FormArtwork from "~/components/Stations/Streamers/Form/Artwork.vue";
import mergeExisting from "~/functions/mergeExisting";
import {BaseEditModalEmits, BaseEditModalProps, useBaseEditModal} from "~/functions/useBaseEditModal";
import {computed, toRef, useTemplateRef, watch} from "vue";
import {useTranslate} from "~/vendor/gettext";
import ModalForm from "~/components/Common/ModalForm.vue";
import Tabs from "~/components/Common/Tabs.vue";
import {storeToRefs} from "pinia";
import {useAppCollectScope} from "~/vendor/regle.ts";
import {
    StationStreamersRecord,
    StationStreamersResponseBody,
    useStationsStreamersForm
} from "~/components/Stations/Streamers/Form/form.ts";

interface StreamersEditModalProps extends BaseEditModalProps {
    newArtUrl: string
}

const props = defineProps<StreamersEditModalProps>();

const emit = defineEmits<BaseEditModalEmits>();

const $modal = useTemplateRef('$modal');

const formStore = useStationsStreamersForm();
const {form, r$, record} = storeToRefs(formStore);
const {$reset: resetForm, setEditMode} = formStore;

const {r$: validater$} = useAppCollectScope('stations-playlists');

const {
    loading,
    error,
    isEditMode,
    clearContents,
    create,
    edit,
    doSubmit,
    close
} = useBaseEditModal<
    StationStreamersRecord,
    StationStreamersResponseBody
>(
    toRef(props, 'createUrl'),
    emit,
    $modal,
    resetForm,
    (data) => {
        record.value = mergeExisting(record.value, data);

        r$.value.$reset({
            toState: mergeExisting(r$.value.$value, data)
        })
    },
    async () => {
        const {valid} = await validater$.$validate();
        return {valid, data: form.value};
    }
);

watch(isEditMode, (newValue) => {
    setEditMode(newValue);
});

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
