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
        <tabs ref="$tabs">
            <form-basic-info/>
            <form-schedule v-model:schedule-items="form.schedule_items" />
            <playlist-link-list-tab
                v-if="isEditMode && form.source === PlaylistSources.Playlists"
                :label="$gettext('Members')"
                :description="$gettext('This playlist group contains the following member playlists:')"
                empty-id="no_playlist_members"
                :empty-label="$gettext('No Members')"
                :empty-text="$gettext('This playlist group has no member playlists.')"
                :items="playlistMembers"
                @navigate="onNavigateToPlaylist"
            />
            <playlist-link-list-tab
                v-if="isEditMode"
                tab-id="group_memberships"
                :label="$gettext('Memberships')"
                :description="$gettext('This playlist is a member of the following playlist groups:')"
                empty-id="no_playlist_groups"
                :empty-label="$gettext('No Group Memberships')"
                :empty-text="$gettext('This playlist is not a member of any playlist group.')"
                :items="playlistGroups"
                @navigate="onNavigateToPlaylist"
            />
            <form-advanced v-if="![PlaylistSources.Playlists, PlaylistSources.Requests].includes(form.source)" />
        </tabs>
    </modal-form>
</template>

<script setup lang="ts">
import { storeToRefs } from "pinia";
import { computed, ref, toRef, useTemplateRef } from "vue";
import { useDialog } from "~/components/Common/Dialogs/useDialog.ts";
import ModalForm from "~/components/Common/ModalForm.vue";
import Tabs from "~/components/Common/Tabs.vue";
import { useNotify } from "~/components/Common/Toasts/useNotify.ts";
import FormAdvanced from "~/components/Stations/Playlists/Form/Advanced.vue";
import FormBasicInfo from "~/components/Stations/Playlists/Form/BasicInfo.vue";
import { useStationsPlaylistsForm } from "~/components/Stations/Playlists/Form/form.ts";
import PlaylistLinkListTab from "~/components/Stations/Playlists/Form/PlaylistLinkListTab.vue";
import FormSchedule from "~/components/Stations/Playlists/Form/Schedule.vue";
import { PlaylistSources } from "~/entities/ApiInterfaces";
import { PlaylistBreadcrumb } from "~/entities/StationPlaylist.ts";
import mergeExisting from "~/functions/mergeExisting.ts";
import { useApiRouter } from "~/functions/useApiRouter.ts";
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

const playlistGroups = ref<PlaylistBreadcrumb[]>([]);
const playlistMembers = ref<PlaylistBreadcrumb[]>([]);

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

        playlistGroups.value =
            (data as { playlist_groups?: PlaylistBreadcrumb[] })
                .playlist_groups ?? [];

        playlistMembers.value = (
            (data as { playlists?: Array<{ id: number; name?: string }> })
                .playlists ?? []
        ).map((member) => ({ id: member.id, name: member.name ?? "" }));
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

const { getStationApiUrl } = useApiRouter();
const { showAlert } = useDialog();
const { $gettext } = useTranslate();
const $tabs = useTemplateRef("$tabs");

const onNavigateToPlaylist = async (
    item: PlaylistBreadcrumb,
): Promise<void> => {
    if (r$.value.$anyEdited) {
        const { value } = await showAlert({
            title: $gettext("Discard unsaved changes?"),
            confirmButtonClass: "btn-danger",
            confirmButtonText: $gettext("Discard"),
        });

        if (!value) {
            return;
        }
    }

    await edit(getStationApiUrl(`/playlist/${item.id}`).value);

    $tabs.value?.selectTab("basic_info");
};

const editMemberships = async (recordUrl: string): Promise<void> => {
    await edit(recordUrl);

    $tabs.value?.selectTab("group_memberships");
};

const langTitle = computed(() => {
    return isEditMode.value
        ? $gettext("Edit Playlist")
        : $gettext("Add Playlist");
});

defineExpose({
    create,
    edit,
    editMemberships,
    close,
});
</script>
