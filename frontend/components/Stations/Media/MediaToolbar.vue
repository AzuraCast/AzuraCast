<template>
    <div
        id="app-toolbar"
        class="d-flex pt-4"
    >
        <div class="flex-fill buttons d-flex align-items-center">
            <span>
                {{ $gettext('With selected:') }}
            </span>

            <div
                class="btn-group btn-group-sm dropdown allow-focus"
            >
                <div class="dropdown">
                    <button
                        ref="$playlistDropdown"
                        class="btn btn-sm btn-primary dropdown-toggle"
                        type="button"
                        data-bs-toggle="dropdown"
                        data-bs-auto-close="outside"
                        aria-expanded="false"
                        :disabled="!hasSelectedItems"
                    >
                        <icon-ic-clear-all/>

                        <span>
                            {{ $gettext('Playlists') }}
                        </span>
                        <span class="caret" />
                    </button>
                    <div
                        class="dropdown-menu"
                        style="min-width: 300px;"
                    >
                        <form
                            class="px-4 py-3"
                            @submit.prevent="setPlaylists"
                        >
                            <div
                                v-for="playlist in playlists"
                                :key="playlist.id"
                                class="form-group"
                            >
                                <div class="custom-control custom-checkbox">
                                    <input
                                        :id="'chk_playlist_' + playlist.id"
                                        v-model="checkedPlaylists"
                                        type="checkbox"
                                        class="custom-control-input"
                                        name="playlists[]"
                                        :value="playlist.id"
                                    >
                                    <label
                                        class="custom-control-label"
                                        :for="'chk_playlist_'+playlist.id"
                                    >
                                        {{ playlist.name }}
                                    </label>
                                </div>
                            </div>

                            <hr class="dropdown-divider">

                            <div class="form-group mt-3 mb-4">
                                <div class="input-group custom-control custom-checkbox">
                                    <div class="input-group-text">
                                        <input
                                            id="chk_playlist_new"
                                            v-model="checkedPlaylists"
                                            type="checkbox"
                                            class="custom-control-input"
                                            value="new"
                                        >
                                        <label
                                            class="custom-control-label"
                                            for="chk_playlist_new"
                                        />
                                    </div>

                                    <input
                                        id="new_playlist_name"
                                        v-model="newPlaylist"
                                        type="text"
                                        class="form-control p-2"
                                        name="new_playlist_name"
                                        style="min-width: 150px;"
                                        :placeholder="$gettext('New Playlist')"
                                    >
                                </div>
                            </div>

                            <div class="buttons">
                                <button
                                    class="btn btn-primary"
                                    type="submit"
                                >
                                    {{ $gettext('Save') }}
                                </button>
                                <button
                                    class="btn btn-warning"
                                    type="button"
                                    @click="clearPlaylists()"
                                >
                                    {{ $gettext('Clear') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <button
                type="button"
                class="btn btn-sm btn-primary"
                :disabled="!hasSelectedItems"
                @click="moveFiles"
            >
                <icon-ic-open-with/>

                <span>
                    {{ $gettext('Move') }}
                </span>
            </button>

            <div class="btn-group btn-group-sm dropdown allow-focus">
                <div class="dropdown">
                    <button
                        class="btn btn-sm btn-secondary dropdown-toggle"
                        type="button"
                        data-bs-toggle="dropdown"
                        aria-expanded="false"
                        :disabled="!hasSelectedItems"
                    >
                        <icon-ic-more-horiz/>
                        <span>
                            {{ $gettext('More') }}
                        </span>
                        <span class="caret" />
                    </button>
                    <ul class="dropdown-menu">
                        <li>
                            <button
                                type="button"
                                :title="$gettext('Queue the selected media to play next')"
                                class="dropdown-item"
                                @click="doQueue"
                            >
                                {{ $gettext('Queue') }}
                            </button>
                        </li>
                        <li>
                            <button
                                v-if="supportsImmediateQueue"
                                type="button"
                                class="dropdown-item"
                                :title="$gettext('Make the selected media play immediately, interrupting existing media')"
                                @click="doImmediateQueue"
                            >
                                {{ $gettext('Play Now') }}
                            </button>
                        </li>
                        <li>
                            <button
                                type="button"
                                class="dropdown-item"
                                :title="$gettext('Analyze and reprocess the selected media')"
                                @click="doReprocess"
                            >
                                {{ $gettext('Reprocess') }}
                            </button>
                        </li>
                        <li>
                            <button
                                type="button"
                                class="dropdown-item"
                                :title="$gettext('Remove any extra metadata (fade points, cue points, etc.) from the selected media')"
                                @click="doClearExtra"
                            >
                                {{ $gettext('Clear Extra Metadata') }}
                            </button>
                        </li>
                    </ul>
                </div>
            </div>

            <button
                type="button"
                class="btn btn-sm btn-danger"
                :disabled="!hasSelectedItems"
                @click="doDelete"
            >
                <icon-ic-delete/>

                <span>
                    {{ $gettext('Delete') }}
                </span>
            </button>
        </div>
        <div class="flex-shrink-0">
            <button
                type="button"
                class="btn btn-sm btn-primary"
                @click="createDirectory"
            >
                <icon-ic-folder/>
                <span>
                    {{ $gettext('New Folder') }}
                </span>
            </button>
        </div>
    </div>
</template>

<script setup lang="ts">
import {Dropdown} from "bootstrap";
import {filter, intersection, map} from "es-toolkit/compat";
import {computed, ref, toRef, useTemplateRef, watch} from "vue";
import {useTranslate} from "~/vendor/gettext";
import {useAxios} from "~/vendor/axios";
import useHandleBatchResponse from "~/components/Stations/Media/useHandleBatchResponse.ts";
import {useNotify} from "~/components/Common/Toasts/useNotify.ts";
import {useDialog} from "~/components/Common/Dialogs/useDialog.ts";
import {MediaInitialPlaylist, MediaSelectedItems} from "~/components/Stations/Media.vue";
import {ApiStationMediaPlaylist} from "~/entities/ApiInterfaces";
import IconIcClearAll from "~icons/ic/baseline-clear-all";
import IconIcDelete from "~icons/ic/baseline-delete";
import IconIcFolder from "~icons/ic/baseline-folder";
import IconIcMoreHoriz from "~icons/ic/baseline-more-horiz";
import IconIcOpenWith from "~icons/ic/baseline-open-with";

const props = defineProps<{
    currentDirectory: string,
    selectedItems: MediaSelectedItems,
    playlists?: MediaInitialPlaylist[],
    batchUrl: string,
    supportsImmediateQueue: boolean
}>();

const emit = defineEmits<{
    (e: 'relist'): void,
    (e: 'add-playlist', playlist: any): void,
    (e: 'move-files'): void,
    (e: 'create-directory'): void
}>();

const selectedItems = toRef(props, 'selectedItems');

const hasSelectedItems = computed(() => {
    return selectedItems.value.all.length > 0;
});

const checkedPlaylists = ref<(number | string)[]>([]);
const newPlaylist = ref('');

watch(selectedItems, (items) => {
    // Get all playlists that are active on ALL selected items.
    const playlistsForItems = map(items.all, (item) => {
        const itemPlaylists = (item.dir?.playlists ?? item.media?.playlists ?? []) as Required<ApiStationMediaPlaylist>[];

        return map(
            filter(
                itemPlaylists,
                (row) => row.folder === null
            ),
            'id'
        );
    });

    // Check the checkboxes for those playlists.
    checkedPlaylists.value = intersection(...playlistsForItems);
});

watch(newPlaylist, (text: string) => {
    if (text !== '') {
        if (!checkedPlaylists.value.includes('new')) {
            checkedPlaylists.value.push('new');
        }
    }
});

const {$gettext} = useTranslate();
const {axios} = useAxios();

const {handleBatchResponse} = useHandleBatchResponse();

const {notifyError} = useNotify();

const notifyNoFiles = () => {
    notifyError($gettext('No files selected.'));
}

const doBatch = async (action: string, successMessage: string, errorMessage: string) => {
    if (hasSelectedItems.value) {
        const {data} = await axios.put(props.batchUrl, {
            'do': action,
            'current_directory': props.currentDirectory,
            'files': selectedItems.value.files,
            'dirs': selectedItems.value.directories
        });

        handleBatchResponse(data, successMessage, errorMessage);
        emit('relist');
    } else {
        notifyNoFiles();
    }
};

const doImmediateQueue = () => {
    void doBatch(
        'immediate',
        $gettext('Files played immediately:'),
        $gettext('Error queueing files:')
    );
};

const doQueue = () => {
    void doBatch(
        'queue',
        $gettext('Files queued for playback:'),
        $gettext('Error queueing files:')
    );
};

const doReprocess = () => {
    void doBatch(
        'reprocess',
        $gettext('Files marked for reprocessing:'),
        $gettext('Error reprocessing files:')
    );
};

const doClearExtra = () => {
    void doBatch(
        'clear-extra',
        $gettext('Extra metadata cleared for files:'),
        $gettext('Error reprocessing files:')
    );
};

const {confirmDelete} = useDialog();

const doDelete = async () => {
    const numFiles = selectedItems.value.all.length;
    const buttonConfirmText = $gettext(
        'Delete %{num} media files?',
        {num: String(numFiles)}
    );

    const {value} = await confirmDelete({
        title: buttonConfirmText,
        confirmButtonText: $gettext('Delete')
    });

    if (!value) {
        return;
    }

    await doBatch(
        'delete',
        $gettext('Files removed:'),
        $gettext('Error removing files:')
    );
};

const $playlistDropdown = useTemplateRef('$playlistDropdown');

const setPlaylists = async () => {
    if ($playlistDropdown.value) {
        Dropdown.getInstance($playlistDropdown.value)?.hide();
    }

    if (hasSelectedItems.value) {
        const {data} = await axios.put(props.batchUrl, {
            'do': 'playlist',
            'playlists': checkedPlaylists.value,
            'new_playlist_name': newPlaylist.value,
            'currentDirectory': props.currentDirectory,
            'files': selectedItems.value.files,
            'dirs': selectedItems.value.directories
        });

        handleBatchResponse(
            data,
            (checkedPlaylists.value.length > 0)
                ? $gettext('Playlists updated for selected files:')
                : $gettext('Playlists cleared for selected files:'),
            $gettext('Error updating playlists:')
        );

        if (data.success) {
            if (data.record) {
                emit('add-playlist', data.record);
            }

            checkedPlaylists.value = [];
            newPlaylist.value = '';
        }

        emit('relist');
    } else {
        notifyNoFiles();
    }
};

const clearPlaylists = () => {
    checkedPlaylists.value = [];
    newPlaylist.value = '';

    void setPlaylists();
};

const moveFiles = () => {
    emit('move-files');
}

const createDirectory = () => {
    emit('create-directory');
}
</script>
