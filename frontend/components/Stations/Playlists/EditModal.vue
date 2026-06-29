<template>
    <modal-form
        ref="$modal"
        :loading="loading"
        :title="langTitle"
        :error="error"
        :disable-save-button="r$.$invalid"
        @submit="doSubmit"
        @hidden="onHidden"
    >
        <tabs>
            <form-basic-info
                ref="$basicInfo"
                :edit-url="editUrl"
            />
            <form-schedule v-model:schedule-items="form.schedule_items" />
            <form-advanced v-if="form.type !== 'clockwheel'"/>
        </tabs>
    </modal-form>
</template>

<script setup lang="ts">
import FormBasicInfo from "~/components/Stations/Playlists/Form/BasicInfo.vue";
import FormSchedule from "~/components/Stations/Playlists/Form/Schedule.vue";
import FormAdvanced from "~/components/Stations/Playlists/Form/Advanced.vue";
import {BaseEditModalEmits, BaseEditModalProps, useBaseEditModal} from "~/functions/useBaseEditModal";
import {computed, nextTick, provide, ref, toRef, useTemplateRef} from "vue";
import {useTranslate} from "~/vendor/gettext";
import {useNotify} from "~/components/Common/Toasts/useNotify.ts";
import ModalForm from "~/components/Common/ModalForm.vue";
import Tabs from "~/components/Common/Tabs.vue";
import {storeToRefs} from "pinia";
import {useAppCollectScope} from "~/vendor/regle.ts";
import {useStationsPlaylistsForm} from "~/components/Stations/Playlists/Form/form.ts";
import mergeExisting from "~/functions/mergeExisting.ts";
import {useAxios} from "~/vendor/axios.ts";
import {PlaylistTypes} from "~/entities/ApiInterfaces.ts";

const props = defineProps<BaseEditModalProps>();

const emit = defineEmits<BaseEditModalEmits & {
    (e: 'needs-restart'): void
}>();

const $modal = useTemplateRef('$modal');
const $basicInfo = useTemplateRef('$basicInfo');

const usedInClockwheels = ref<Array<{id: number, name: string}>>([]);
provide('usedInClockwheels', usedInClockwheels);

const isClockwheelMode = ref(false);
provide('isClockwheelMode', isClockwheelMode);

const {notifySuccess, notifyError} = useNotify();
const {axios} = useAxios();

const formStore = useStationsPlaylistsForm();
const {form, r$} = storeToRefs(formStore);
const {$reset: resetForm} = formStore;

const {r$: validatedr$} = useAppCollectScope('stations-playlists');

const {
    loading,
    error,
    isEditMode,
    editUrl,
    clearContents,
    create,
    edit,
    doSubmit,
    close
} = useBaseEditModal(
    toRef(props, 'createUrl'),
    emit,
    $modal,
    resetForm,
    (data) => {
        const record = data as Record<string, unknown>;
        usedInClockwheels.value = (record.used_in_clockwheels as Array<{id: number, name: string}>) ?? [];
        isClockwheelMode.value = data.type === PlaylistTypes.Clockwheel;
        r$.value.$reset({
            toState: mergeExisting(r$.value.$value, data)
        })
    },
    async () => {
        const {valid} = await validatedr$.$validate();
        return {valid, data: form.value};
    },
    {
        onSubmitSuccess: async (data) => {
            const cwChildren = $basicInfo.value?.$clockwheelChildren;
            if (form.value.type === PlaylistTypes.Clockwheel && cwChildren) {
                const playlistUrl = isEditMode.value && editUrl.value
                    ? editUrl.value
                    : data?.links?.self;

                if (playlistUrl) {
                    try {
                        await axios.put(
                            playlistUrl + '/children',
                            cwChildren.children.filter(
                                (c: any) => c.child_playlist_id !== ''
                            )
                        );
                    } catch (e) {
                        notifyError();
                        console.error(e);
                        return;
                    }
                }
            }

            notifySuccess();
            emit('relist');
            emit('needs-restart');
            close();
        },
    }
);

const onHidden = (): void => {
    clearContents();
    isClockwheelMode.value = false;
    usedInClockwheels.value = [];
};

const {$gettext} = useTranslate();

const langTitle = computed(() => {
    if (isClockwheelMode.value) {
        return isEditMode.value
            ? $gettext('Edit Clockwheel')
            : $gettext('Add Clockwheel');
    }
    return isEditMode.value
        ? $gettext('Edit Playlist')
        : $gettext('Add Playlist');
});

const createClockwheel = (): void => {
    isClockwheelMode.value = true;
    create();
    void nextTick(() => {
        form.value.type = PlaylistTypes.Clockwheel;
    });
};

defineExpose({
    create,
    createClockwheel,
    edit,
    close
});
</script>
