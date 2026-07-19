<template>
    <modal
        id="group_membership_modal"
        ref="$modal"
        size="md"
        :title="$gettext('Group Memberships of &quot;%{name}&quot;', {name: playlistName})"
    >
        <p>
            {{
                $gettext('This playlist is a member of the following playlist groups:')
            }}
        </p>

        <ul class="list-group list-group-flush border rounded">
            <li
                v-for="group in groups"
                :key="group.id"
                class="list-group-item p-0"
            >
                <a
                    href="#"
                    class="group-membership-item d-block w-100 p-3"
                    @click.prevent="doSelect(group.name)"
                >
                    {{ group.name }}
                </a>
            </li>
        </ul>

        <template #modal-footer>
            <button
                class="btn btn-secondary"
                type="button"
                @click="hide"
            >
                {{ $gettext('Close') }}
            </button>
        </template>
    </modal>
</template>

<script setup lang="ts">
import { ref, useTemplateRef } from "vue";
import Modal from "~/components/Common/Modal.vue";
import { PlaylistBreadcrumb } from "~/entities/StationPlaylist.ts";
import { useHasModal } from "~/functions/useHasModal.ts";
import { useTranslate } from "~/vendor/gettext";

const emit = defineEmits<(e: "select", name: string) => void>();

const playlistName = ref<string>("");
const groups = ref<PlaylistBreadcrumb[]>([]);

const $modal = useTemplateRef("$modal");
const { show, hide } = useHasModal($modal);

const { $gettext } = useTranslate();

const open = (newPlaylistName: string, newGroups: PlaylistBreadcrumb[]) => {
    playlistName.value = newPlaylistName;
    groups.value = newGroups;

    show();
};

const doSelect = (name: string) => {
    emit("select", name);
    hide();
};

defineExpose({
    open,
});
</script>

<style lang="scss" scoped>
.group-membership-item {
    cursor: pointer;

    &:hover {
        background-image: linear-gradient(to bottom, rgba(0, 0, 0, .12), rgba(0, 0, 0, .12));
    }
}
</style>
