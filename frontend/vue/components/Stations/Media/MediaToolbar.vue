<template>
    <div
        id="app-toolbar"
        class="row pt-4"
    >
        <div class="col-md-8 buttons">
            <div class="btn-group dropdown allow-focus">
                <b-dropdown
                    ref="$setPlaylistsDropdown"
                    v-b-tooltip.hover
                    size="sm"
                    variant="primary"
                    :title="$gettext('Set or clear playlists from the selected media')"
                >
                    <template #button-content>
                        <icon icon="clear_all" />
                        {{ $gettext('Playlists') }}
                        <span class="caret" />
                    </template>
                    <b-dropdown-form
                        class="pt-2"
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
                        <div class="form-group">
                            <div class="custom-control custom-checkbox">
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
                                >
                                    <input
                                        id="new_playlist_name"
                                        v-model="newPlaylist"
                                        type="text"
                                        class="form-control p-2"
                                        name="new_playlist_name"
                                        style="min-width: 150px;"
                                        :placeholder="$gettext('New Playlist')"
                                    >
                                </label>
                            </div>
                        </div>

                        <div class="buttons">
                            <b-button
                                type="submit"
                                size="sm"
                                variant="primary"
                            >
                                {{ $gettext('Save') }}
                            </b-button>
                            <b-button
                                type="button"
                                size="sm"
                                variant="warning"
                                @click="clearPlaylists()"
                            >
                                {{ $gettext('Clear') }}
                            </b-button>
                        </div>
                    </b-dropdown-form>
                </b-dropdown>
            </div>
            <b-button
                v-b-modal.move_file
                size="sm"
                variant="primary"
            >
                <icon icon="open_with" />
                {{ $gettext('Move') }}
            </b-button>
            <b-dropdown
                size="sm"
                variant="default"
            >
                <template #button-content>
                    <icon icon="more_horiz" />
                    {{ $gettext('More') }}
                </template>
                <b-dropdown-item
                    v-b-tooltip.hover
                    :title="$gettext('Queue the selected media to play next')"
                    @click="doQueue"
                >
                    {{ $gettext('Queue') }}
                </b-dropdown-item>
                <b-dropdown-item
                    v-if="supportsImmediateQueue"
                    v-b-tooltip.hover
                    :title="$gettext('Make the selected media play immediately, interrupting existing media')"
                    @click="doImmediateQueue"
                >
                    {{ $gettext('Play Now') }}
                </b-dropdown-item>
                <b-dropdown-item
                    v-b-tooltip.hover
                    :title="$gettext('Analyze and reprocess the selected media')"
                    @click="doReprocess"
                >
                    {{ $gettext('Reprocess') }}
                </b-dropdown-item>
            </b-dropdown>

            <b-button
                size="sm"
                variant="danger"
                @click="doDelete"
            >
                <icon icon="delete" />
                {{ $gettext('Delete') }}
            </b-button>
        </div>
        <div class="col-md-4 text-right">
            <b-button
                v-b-modal.create_directory
                size="sm"
                variant="primary"
            >
                <icon icon="folder" />
                {{ $gettext('New Folder') }}
            </b-button>
        </div>
    </div>
</template>

<script setup>
import {forEach, intersection, map} from 'lodash';
import Icon from '~/components/Common/Icon';
import '~/vendor/sweetalert';
import {h, ref, toRef, watch} from "vue";
import {useTranslate} from "~/vendor/gettext";
import {useNotify} from "~/vendor/bootstrapVue";
import {useAxios} from "~/vendor/axios";
import {useSweetAlert} from "~/vendor/sweetalert";

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

const emit = defineEmits(['relist', 'add-playlist']);

const checkedPlaylists = ref([]);
const newPlaylist = ref('');

const {$gettext} = useTranslate();
const langErrors = $gettext('The request could not be processed.');

watch(toRef(props, 'selectedItems'), (items) => {
    // Get all playlists that are active on ALL selected items.
    let playlistsForItems = map(items.all, (item) => {
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

const {wrapWithLoading, notifySuccess, notifyError} = useNotify();
const {axios} = useAxios();

const notifyNoFiles = () => {
    notifyError($gettext('No files selected.'));
};

const doBatch = (action, notifyMessage) => {
    if (props.selectedItems.all.length) {
        wrapWithLoading(
            axios.put(props.batchUrl, {
                'do': action,
                'current_directory': props.currentDirectory,
                'files': props.selectedItems.files,
                'dirs': props.selectedItems.directories
            })
        ).then((resp) => {
            if (resp.data.success) {
                let allItemNodes = [];
                forEach(props.selectedItems.all, (item) => {
                    allItemNodes.push(h('div', {}, item.path_short));
                });

                notifySuccess(allItemNodes, {
                    title: notifyMessage
                });
            } else {
                let errorNodes = [];
                forEach(resp.data.errors, (error) => {
                    errorNodes.push(h('div', {}, error));
                });

                notifyError(errorNodes, {
                    title: langErrors
                });
            }

            emit('relist');
        });
    } else {
        notifyNoFiles();
    }
};

const doImmediateQueue = () => {
    doBatch('immediate', $gettext('Files played immediately:'));
};

const doQueue = () => {
    doBatch('queue', $gettext('Files queued for playback:'));
};

const doReprocess = () => {
    doBatch('reprocess', $gettext('Files marked for reprocessing:'));
};

const {confirmDelete} = useSweetAlert();

const doDelete = () => {
    let numFiles = this.selectedItems.all.length;
    let buttonConfirmText = $gettext(
        'Delete %{ num } media files?',
        {num: numFiles}
    );

    confirmDelete({
        title: buttonConfirmText,
    }).then((result) => {
        if (result.value) {
            doBatch('delete', $gettext('Files removed:'));
        }
    });
};

const $setPlaylistsDropdown = ref(); // Template Ref

const setPlaylists = () => {
    $setPlaylistsDropdown.value.hide();

    if (props.selectedItems.all.length) {
        wrapWithLoading(
            axios.put(props.batchUrl, {
                'do': 'playlist',
                'playlists': checkedPlaylists.value,
                'new_playlist_name': newPlaylist.value,
                'currentDirectory': props.currentDirectory,
                'files': props.selectedItems.files,
                'dirs': props.selectedItems.directories
            })
        ).then((resp) => {
            if (resp.data.success) {
                if (resp.data.record) {
                    emit('add-playlist', resp.data.record);
                }

                let notifyMessage = (checkedPlaylists.value.length > 0)
                    ? $gettext('Playlists updated for selected files:')
                    : $gettext('Playlists cleared for selected files:');

                let allItemNodes = [];
                forEach(props.selectedItems.all, (item) => {
                    allItemNodes.push(h('div', {}, item.path_short));
                });

                notifySuccess(allItemNodes, {
                    title: notifyMessage
                });

                checkedPlaylists.value = [];
                newPlaylist.value = '';
            } else {
                let errorNodes = [];
                forEach(resp.data.errors, (error) => {
                    errorNodes.push(h('div', {}, error));
                });

                notifyError(errorNodes, {
                    title: langErrors
                });
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
</script>
