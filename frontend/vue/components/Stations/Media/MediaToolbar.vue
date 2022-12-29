<template>
    <div
        id="app-toolbar"
        class="row pt-4"
    >
        <div class="col-md-8 buttons">
            <div class="btn-group dropdown allow-focus">
                <b-dropdown
                    ref="setPlaylistsDropdown"
                    v-b-tooltip.hover
                    size="sm"
                    variant="primary"
                    :title="langPlaylistDropdown"
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
                                        :placeholder="langNewPlaylist"
                                    >
                                </label>
                            </div>
                        </div>

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
                    {{ langMore }}
                </template>
                <b-dropdown-item
                    v-b-tooltip.hover
                    :title="langQueue"
                    @click="doQueue"
                >
                    {{ $gettext('Queue') }}
                </b-dropdown-item>
                <b-dropdown-item
                    v-if="supportsImmediateQueue"
                    v-b-tooltip.hover
                    :title="langImmediateQueue"
                    @click="doImmediateQueue"
                >
                    {{ $gettext('Play Now') }}
                </b-dropdown-item>
                <b-dropdown-item
                    v-b-tooltip.hover
                    :title="langReprocess"
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
<script>
import _ from 'lodash';
import Icon from '~/components/Common/Icon';
import '~/vendor/sweetalert';

export default {
    name: 'StationMediaToolbar',
    components: {Icon},
    props: {
        currentDirectory: String,
        selectedItems: Object,
        playlists: Array,
        batchUrl: String,
        supportsImmediateQueue: Boolean
    },
    data () {
        return {
            checkedPlaylists: [],
            newPlaylist: '',
        };
    },
    computed: {
        langPlaylistDropdown() {
            return this.$gettext('Set or clear playlists from the selected media');
        },
        langNewPlaylist() {
            return this.$gettext('New Playlist');
        },
        langMore() {
            return this.$gettext('More');
        },
        langImmediateQueue() {
            return this.$gettext('Make the selected media play immediately, interrupting existing media');
        },
        langQueue() {
            return this.$gettext('Queue the selected media to play next');
        },
        langReprocess() {
            return this.$gettext('Analyze and reprocess the selected media');
        },
        langErrors() {
            return this.$gettext('The request could not be processed.');
        },
    },
    watch: {
        selectedItems (items) {
            // Get all playlists that are active on ALL selected items.
            let playlistsForItems = _.map(items.all, (item) => {
                return _.map(item.playlists, 'id');
            });

            // Check the checkboxes for those playlists.
            this.checkedPlaylists = _.intersection(...playlistsForItems);
        },
        newPlaylist (text) {
            if (text !== '') {
                if (!this.checkedPlaylists.includes('new')) {
                    this.checkedPlaylists.push('new');
                }
            }
        }
    },
    methods: {
        doImmediateQueue() {
            this.doBatch('immediate', this.$gettext('Files played immediately:'));
        },
        doQueue() {
            this.doBatch('queue', this.$gettext('Files queued for playback:'));
        },
        doReprocess() {
            this.doBatch('reprocess', this.$gettext('Files marked for reprocessing:'));
        },
        doDelete() {
            let buttonConfirmText = this.$gettext('Delete %{ num } media files?');
            let numFiles = this.selectedItems.all.length;

            this.$confirmDelete({
                title: this.$gettextInterpolate(buttonConfirmText, {num: numFiles}),
            }).then((result) => {
                if (result.value) {
                    this.doBatch('delete', this.$gettext('Files removed:'));
                }
            });
        },
        doBatch (action, notifyMessage) {
            if (this.selectedItems.all.length) {
                this.$wrapWithLoading(
                    this.axios.put(this.batchUrl, {
                        'do': action,
                        'current_directory': this.currentDirectory,
                        'files': this.selectedItems.files,
                        'dirs': this.selectedItems.directories
                    })
                ).then((resp) => {
                    if (resp.data.success) {
                        let allItemNodes = [];
                        _.forEach(this.selectedItems.all, (item) => {
                            allItemNodes.push(this.$createElement('div', {}, item.path_short));
                        });

                        this.$notifySuccess(allItemNodes, {
                            title: notifyMessage
                        });
                    } else {
                        let errorNodes = [];
                        _.forEach(resp.data.errors, (error) => {
                            errorNodes.push(this.$createElement('div', {}, error));
                        });

                        this.$notifyError(errorNodes, {
                            title: this.langErrors
                        });
                    }

                    this.$emit('relist');
                });
            } else {
                this.notifyNoFiles();
            }
        },
        clearPlaylists () {
            this.checkedPlaylists = [];
            this.newPlaylist = '';

            this.setPlaylists();
        },
        setPlaylists () {
            this.$refs.setPlaylistsDropdown.hide();

            if (this.selectedItems.all.length) {
                this.$wrapWithLoading(
                    this.axios.put(this.batchUrl, {
                        'do': 'playlist',
                        'playlists': this.checkedPlaylists,
                        'new_playlist_name': this.newPlaylist,
                        'currentDirectory': this.currentDirectory,
                        'files': this.selectedItems.files,
                        'dirs': this.selectedItems.directories
                    })
                ).then((resp) => {
                    if (resp.data.success) {
                        if (resp.data.record) {
                            this.$emit('add-playlist', resp.data.record);
                        }

                        let notifyMessage = (this.checkedPlaylists.length > 0)
                            ? this.$gettext('Playlists updated for selected files:')
                            : this.$gettext('Playlists cleared for selected files:');

                        let allItemNodes = [];
                        _.forEach(this.selectedItems.all, (item) => {
                            allItemNodes.push(this.$createElement('div', {}, item.path_short));
                        });

                        this.$notifySuccess(allItemNodes, {
                            title: notifyMessage
                        });

                        this.checkedPlaylists = [];
                        this.newPlaylist = '';
                    } else {
                        let errorNodes = [];
                        _.forEach(resp.data.errors, (error) => {
                            errorNodes.push(this.$createElement('div', {}, error));
                        });

                        this.$notifyError(errorNodes, {
                            title: this.langErrors
                        });
                    }

                    this.$emit('relist');
                });
            } else {
                this.notifyNoFiles();
            }
        },
        notifyNoFiles() {
            this.$notifyError(this.$gettext('No files selected.'));
        }
    }
};
</script>
