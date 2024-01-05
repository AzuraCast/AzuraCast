<template>
    <div
        id="app-toolbar"
        class="row pt-4"
    >
        <div class="col-md-8 buttons">
            <div class="btn-group dropdown allow-focus">
                <div class="dropdown">
                    <button
                        class="btn btn-primary dropdown-toggle"
                        type="button"
                        data-bs-toggle="dropdown"
                        data-bs-auto-close="outside"
                        aria-expanded="false"
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
                class="btn btn-primary"
                @click="moveFiles"
            >
                <icon :icon="IconMove" />
                <span>
                    {{ $gettext('Move') }}
                </span>
            </button>

            <div class="btn-group dropdown allow-focus">
                <div class="dropdown">
                    <button
                        class="btn btn-secondary dropdown-toggle"
                        type="button"
                        data-bs-toggle="dropdown"
                        aria-expanded="false"
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
                    </ul>
                </div>
            </div>

            <button
                type="button"
                class="btn btn-danger"
                @click="doDelete"
            >
                <icon :icon="IconDelete" />
                <span>
                    {{ $gettext('Delete') }}
                </span>
            </button>
        </div>
        <div class="col-md-4 text-end">
            <button
                type="button"
                class="btn btn-primary"
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
import {intersection, map} from 'lodash';
import Icon from '~/components/Common/Icon.vue';
import '~/vendor/sweetalert';
import {ref, toRef, watch} from "vue";
import {useTranslate} from "~/vendor/gettext";
import {useAxios} from "~/vendor/axios";
import {useSweetAlert} from "~/vendor/sweetalert";
import {IconClearAll, IconDelete, IconFolder, IconMoreHoriz, IconMove} from "~/components/Common/icons";
import useHandleBatchResponse from "~/components/Stations/Media/useHandleBatchResponse.ts";

const props = defineProps({
    currentDirectory: {
        type: String,
        required: true
    },
    selectedItems: {
        type: Object,
        required: true
    },
    playlists: {
        type: Array,
        default: () => {
            return [];
        }
    },
    batchUrl: {
        type: String,
        required: true
    },
    supportsImmediateQueue: {
        type: Boolean,
        required: true
    }
});

const emit = defineEmits(['relist', 'add-playlist', 'move-files', 'create-directory']);

const checkedPlaylists = ref([]);
const newPlaylist = ref('');

watch(toRef(props, 'selectedItems'), (items) => {
    // Get all playlists that are active on ALL selected items.
    const playlistsForItems = map(items.all, (item) => {
        return map(item.playlists, 'id');
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

const {notifyNoFiles, handleBatchResponse} = useHandleBatchResponse();

const doBatch = (action, successMessage, errorMessage) => {
    if (props.selectedItems.all.length) {
        axios.put(props.batchUrl, {
            'do': action,
            'current_directory': props.currentDirectory,
            'files': props.selectedItems.files,
            'dirs': props.selectedItems.directories
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

const {confirmDelete} = useSweetAlert();

const doDelete = () => {
    const numFiles = props.selectedItems.all.length;
    const buttonConfirmText = $gettext(
        'Delete %{ num } media files?',
        {num: numFiles}
    );

    confirmDelete({
        title: buttonConfirmText,
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

const setPlaylists = () => {
    if (props.selectedItems.all.length) {
        axios.put(props.batchUrl, {
            'do': 'playlist',
            'playlists': checkedPlaylists.value,
            'new_playlist_name': newPlaylist.value,
            'currentDirectory': props.currentDirectory,
            'files': props.selectedItems.files,
            'dirs': props.selectedItems.directories
        }).then(({data}) => {
            if (data.success) {
                if (data.record) {
                    emit('add-playlist', data.record);
                }

                checkedPlaylists.value = [];
                newPlaylist.value = '';
            }

            handleBatchResponse(
                data,
                (checkedPlaylists.value.length > 0)
                    ? $gettext('Playlists updated for selected files:')
                    : $gettext('Playlists cleared for selected files:'),
                $gettext('Error updating playlists:')
            );

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
