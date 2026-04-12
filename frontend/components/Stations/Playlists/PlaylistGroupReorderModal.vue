<template>
    <modal
        id="playlist_group_reorder_modal"
        ref="$modal"
        size="lg"
        :title="$gettext('Reorder Playlist Group')"
        hide-footer
    >
        <table class="table table-striped sortable mb-0">
            <thead>
                <tr>
                    <th style="width: 5%">
                    &nbsp;
                    </th>
                    <th style="width: 45%;">
                        {{ $gettext('Playlist') }}
                    </th>
                    <th style="width: 20%;">
                        {{ $gettext('Source') }}
                    </th>
                    <th style="width: 10%;">
                        {{ $gettext('# Entries') }}
                    </th>
                    <th style="width: 20%;">
                        {{ $gettext('Actions') }}
                    </th>
                </tr>
            </thead>
            <tbody ref="$tbody">
                <tr
                    v-for="(member, index) in members"
                    :key="member.id"
                    class="align-middle"
                >
                    <td>
                        <icon-bi-grip-vertical class="text-muted" />
                    </td>
                    <td>
                        <span class="typography-subheading">{{ member.name }}</span>
                    </td>
                    <td>
                        <span class="badge text-bg-secondary">
                            <template v-if="member.source === PlaylistSources.Songs">
                                {{ $gettext('Song-based') }}
                            </template>
                            <template v-else-if="member.source === PlaylistSources.Playlists">
                                {{ $gettext('Playlist Group') }}
                            </template>
                            <template v-else-if="member.source === PlaylistSources.RemoteUrl">
                                {{ $gettext('Remote URL') }}
                            </template>
                        </span>
                    </td>
                    <td>
                        <span
                            v-if="member.source === PlaylistSources.Songs"
                            v-text="member.num_songs"
                        />
                        <span
                            v-else-if="member.source === PlaylistSources.Playlists"
                            v-text="member.playlists.length"
                        />
                    </td>
                    <td>
                        <div class="btn-group btn-group-sm">
                            <button
                                v-if="index + 1 < members.length"
                                type="button"
                                class="btn btn-secondary"
                                :title="$gettext('Move to Bottom')"
                                @click.prevent="moveToBottom(index)"
                            >
                                <icon-bi-chevron-bar-down />
                            </button>
                            <button
                                v-if="index + 1 < members.length"
                                type="button"
                                class="btn btn-primary"
                                :title="$gettext('Move Down')"
                                @click.prevent="moveDown(index)"
                            >
                                <icon-bi-chevron-down />
                            </button>
                            <button
                                v-if="index > 0"
                                type="button"
                                class="btn btn-primary"
                                :title="$gettext('Move Up')"
                                @click.prevent="moveUp(index)"
                            >
                                <icon-bi-chevron-up />
                            </button>
                            <button
                                v-if="index > 0"
                                type="button"
                                class="btn btn-secondary"
                                :title="$gettext('Move to Top')"
                                @click.prevent="moveToTop(index)"
                            >
                                <icon-bi-chevron-bar-up />
                            </button>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </modal>
</template>

<script setup lang="ts">
import {ref, useTemplateRef} from "vue";
import {useAxios} from "~/vendor/axios";
import {useNotify} from "~/components/Common/Toasts/useNotify.ts";
import {useTranslate} from "~/vendor/gettext";
import Modal from "~/components/Common/Modal.vue";
import {useHasModal} from "~/functions/useHasModal.ts";
import {useDraggable} from "vue-draggable-plus";
import {PlaylistSources} from "~/entities/ApiInterfaces.ts";
import {StationPlaylistGroupMemberEnriched} from "~/entities/StationPlaylist.ts";
import IconBiGripVertical from "~icons/bi/grip-vertical";
import IconBiChevronBarDown from "~icons/bi/chevron-bar-down";
import IconBiChevronBarUp from "~icons/bi/chevron-bar-up";
import IconBiChevronDown from "~icons/bi/chevron-down";
import IconBiChevronUp from "~icons/bi/chevron-up";

const emit = defineEmits<{
    (e: 'relist'): void
}>();

const membersUrl = ref<string | null>(null);
const members = ref<StationPlaylistGroupMemberEnriched[]>([]);

const $tbody = useTemplateRef('$tbody');
const $modal = useTemplateRef('$modal');
const {show} = useHasModal($modal);

const open = (url: string, initialMembers: StationPlaylistGroupMemberEnriched[]) => {
    membersUrl.value = url;
    members.value = [...initialMembers].sort((a, b) => a.weight - b.weight);
    show();

    useDraggable($tbody, members, {
        onEnd() {
            void save();
        }
    });
};

const {axios} = useAxios();
const {notifySuccess} = useNotify();
const {$gettext} = useTranslate();

const save = async () => {
    if (!membersUrl.value) {
        return;
    }

    await axios.put(membersUrl.value, {
        members: members.value.map((member, index) => ({
            id: member.id,
            weight: index + 1,
        })),
    });

    notifySuccess($gettext('Playlist group order set.'));
    emit('relist');
};

const moveDown = (index: number) => {
    const item = members.value.splice(index, 1)[0];
    members.value.splice(index + 1, 0, item);
    void save();
};

const moveToBottom = (index: number) => {
    const item = members.value.splice(index, 1)[0];
    members.value.splice(members.value.length, 0, item);
    void save();
};

const moveUp = (index: number) => {
    const item = members.value.splice(index, 1)[0];
    members.value.splice(index - 1, 0, item);
    void save();
};

const moveToTop = (index: number) => {
    const item = members.value.splice(index, 1)[0];
    members.value.splice(0, 0, item);
    void save();
};

defineExpose({
    open
});
</script>

<style lang="scss">
table.sortable {
    cursor: pointer;
}
</style>
