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
            <mount-form-basic-info
                :station-frontend-type="frontendType"
            />
            <mount-form-auto-dj
                :station-frontend-type="frontendType"
            />
            <mount-form-intro
                v-model="form.intro_file"
                :record-has-intro="record.intro_path !== null"
                :new-intro-url="newIntroUrl"
                :edit-intro-url="record.links.intro"
            />
            <mount-form-advanced
                :station-frontend-type="frontendType"
            />
        </tabs>
    </modal-form>
</template>

<script setup lang="ts">
import MountFormBasicInfo from "~/components/Stations/Mounts/Form/BasicInfo.vue";
import MountFormAutoDj from "~/components/Stations/Mounts/Form/AutoDj.vue";
import MountFormAdvanced from "~/components/Stations/Mounts/Form/Advanced.vue";
import MountFormIntro from "~/components/Stations/Mounts/Form/Intro.vue";
import mergeExisting from "~/functions/mergeExisting";
import {BaseEditModalEmits, BaseEditModalProps, useBaseEditModal} from "~/functions/useBaseEditModal";
import {computed, toRef, useTemplateRef} from "vue";
import {useNotify} from "~/components/Common/Toasts/useNotify.ts";
import {useTranslate} from "~/vendor/gettext";
import ModalForm from "~/components/Common/ModalForm.vue";
import Tabs from "~/components/Common/Tabs.vue";
import {storeToRefs} from "pinia";
import {
    StationMountHttpResponse,
    StationMountRecord,
    useStationsMountsForm
} from "~/components/Stations/Mounts/Form/form.ts";
import {useStationData} from "~/functions/useStationQuery.ts";
import {toRefs} from "@vueuse/core";

const props = defineProps<BaseEditModalProps & {
    newIntroUrl: string
}>();

const emit = defineEmits<BaseEditModalEmits & {
    (e: 'needs-restart'): void
}>();

const stationData = useStationData();
const {frontendType} = toRefs(stationData);

const $modal = useTemplateRef('$modal');

const {notifySuccess} = useNotify();

const formStore = useStationsMountsForm();
const {form, record, r$} = storeToRefs(formStore);
const {$reset: resetForm} = formStore;

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
    StationMountRecord,
    StationMountHttpResponse
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
        const {valid} = await r$.value.$validate();
        return {valid, data: form.value};
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
        ? $gettext('Edit Mount Point')
        : $gettext('Add Mount Point');
});

defineExpose({
    create,
    edit,
    close
});
</script>
