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
                        <icon :icon="IconClearAll" />
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
                <icon :icon="IconMove" />
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
                        <icon :icon="IconMoreHoriz" />
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
                <icon :icon="IconDelete" />
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
                <icon :icon="IconFolder" />
                <span>
                    {{ $gettext('New Folder') }}
                </span>
            </button>
        </div>
    </div>
</template>

<script setup lang="ts">
import {Dropdown} from 'bootstrap';
import {intersection, map} from 'lodash';
import Icon from '~/components/Common/Icon.vue';
import {computed, ref, toRef, watch} from "vue";
import {useTranslate} from "~/vendor/gettext";
import {useAxios} from "~/vendor/axios";
import {IconClearAll, IconDelete, IconFolder, IconMoreHoriz, IconMove} from "~/components/Common/icons";
import useHandleBatchResponse from "~/components/Stations/Media/useHandleBatchResponse.ts";
import {useNotify} from "~/functions/useNotify.ts";
import {useDialog} from "~/functions/useDialog.ts";
import {MediaInitialPlaylist, MediaSelectedItems} from "~/components/Stations/Media.vue";

const props = defineProps<{
    currentDirectory: string,
    selectedItems: MediaSelectedItems,
    playlists?: MediaInitialPlaylist[],
    batchUrl: string,
    supportsImmediateQueue: boolean
}>();

const emit = defineEmits(['relist', 'add-playlist', 'move-files', 'create-directory']);

const selectedItems = toRef(props, 'selectedItems');

const hasSelectedItems = computed(() => {
    return selectedItems.value.all.length > 0;
});

const checkedPlaylists = ref([]);
const newPlaylist = ref('');

watch(selectedItems, (items) => {
    // Get all playlists that are active on ALL selected items.
    const playlistsForItems = map(items.all, (item) => {
        const itemPlaylists = item.dir?.playlists ?? item.media?.playlists ?? [];
        return map(itemPlaylists, 'id');
    });

    // Check the checkboxes for those playlists.
    checkedPlaylists.value = intersection(...playlistsForItems);
});

watch(newPlaylist, (text) => {
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

const doBatch = (action, successMessage, errorMessage) => {
    if (hasSelectedItems.value) {
        axios.put(props.batchUrl, {
            'do': action,
            'current_directory': props.currentDirectory,
            'files': selectedItems.value.files,
            'dirs': selectedItems.value.directories
        }).then(({data}) => {
            handleBatchResponse(data, successMessage, errorMessage);
            emit('relist');
        });
    } else {
        notifyNoFiles();
    }
};

const doImmediateQueue = () => {
    doBatch(
        'immediate',
        $gettext('Files played immediately:'),
        $gettext('Error queueing files:')
    );
};

const doQueue = () => {
    doBatch(
        'queue',
        $gettext('Files queued for playback:'),
        $gettext('Error queueing files:')
    );
};

const doReprocess = () => {
    doBatch(
        'reprocess',
        $gettext('Files marked for reprocessing:'),
        $gettext('Error reprocessing files:')
    );
};

const doClearExtra = () => {
    doBatch(
        'clear-extra',
        $gettext('Extra metadata cleared for files:'),
        $gettext('Error reprocessing files:')
    );
};

const {confirmDelete} = useDialog();

const doDelete = () => {
    const numFiles = selectedItems.value.all.length;
    const buttonConfirmText = $gettext(
        'Delete %{num} media files?',
        {num: String(numFiles)}
    );

    confirmDelete({
        title: buttonConfirmText,
        confirmButtonText: $gettext('Delete')
    }).then((result) => {
        if (result.value) {
            doBatch(
                'delete',
                $gettext('Files removed:'),
                $gettext('Error removing files:')
            );
        }
    });
};

const $playlistDropdown = ref<InstanceType<typeof HTMLDivElement> | null>(null);

const setPlaylists = () => {
    if ($playlistDropdown.value) {
        Dropdown.getInstance($playlistDropdown.value).hide();
    }

    if (hasSelectedItems.value) {
        axios.put(props.batchUrl, {
            'do': 'playlist',
            'playlists': checkedPlaylists.value,
            'new_playlist_name': newPlaylist.value,
            'currentDirectory': props.currentDirectory,
            'files': selectedItems.value.files,
            'dirs': selectedItems.value.directories
        }).then(({data}) => {
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
        });
    } else {
        notifyNoFiles();
    }
};

const clearPlaylists = () => {
    checkedPlaylists.value = [];
    newPlaylist.value = '';

    setPlaylists();
};

const moveFiles = () => {
    emit('move-files');
}

const createDirectory = () => {
    emit('create-directory');
}
</script>
