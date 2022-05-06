<template>
    <div class="row pt-4" id="app-toolbar">
        <div class="col-md-8">
            <div class="btn-group dropdown allow-focus">
                <b-dropdown size="sm" variant="primary" ref="setPlaylistsDropdown" v-b-tooltip.hover
                            :title="langPlaylistDropdown">
                    <template #button-content>
                        <icon icon="clear_all"></icon>
                        <translate key="lang_playlists_title">Playlists</translate>
                        <span class="caret"></span>
                    </template>
                    <b-dropdown-form class="pt-2" @submit.prevent="setPlaylists">
                        <div v-for="playlist in playlists" class="form-group">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input"
                                       v-bind:id="'chk_playlist_' + playlist.id" name="playlists[]"
                                       v-model="checkedPlaylists" v-bind:value="playlist.id">
                                <label class="custom-control-label" v-bind:for="'chk_playlist_'+playlist.id">
                                    {{ playlist.name }}
                                </label>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="chk_playlist_new"
                                       v-model="checkedPlaylists" value="new">
                                <label class="custom-control-label" for="chk_playlist_new">
                                    <input type="text" class="form-control p-2" id="new_playlist_name"
                                           name="new_playlist_name" v-model="newPlaylist" style="min-width: 150px;"
                                           :placeholder="langNewPlaylist">
                                </label>
                            </div>
                        </div>

                        <b-button type="submit" size="sm" variant="primary">
                            <translate key="lang_btn_save">Save</translate>
                        </b-button>
                        <b-button type="button" size="sm" variant="warning" @click="clearPlaylists()">
                            <translate key="lang_btn_clear">Clear</translate>
                        </b-button>
                    </b-dropdown-form>
                </b-dropdown>
            </div>
            <b-button size="sm" variant="primary" v-b-modal.move_file>
                <icon icon="open_with"></icon>
                <translate key="lang_btn_move">Move</translate>
            </b-button>
            <b-dropdown size="sm" variant="default">
                <template #button-content>
                    <icon icon="more_horiz"></icon>
                    {{ langMore }}
                </template>
                <b-dropdown-item @click="doQueue" v-b-tooltip.hover :title="langQueue">
                    <translate key="lang_btn_queue">Queue</translate>
                </b-dropdown-item>
                <b-dropdown-item v-if="supportsImmediateQueue" @click="doImmediateQueue" v-b-tooltip.hover
                                 :title="langImmediateQueue">
                    <translate key="lang_btn_immediate_queue">Play Now</translate>
                </b-dropdown-item>
                <b-dropdown-item @click="doReprocess" v-b-tooltip.hover :title="langReprocess">
                    <translate key="lang_btn_reprocess">Reprocess</translate>
                </b-dropdown-item>
            </b-dropdown>

            <b-button size="sm" variant="danger" @click="doDelete">
                <icon icon="delete"></icon>
                <translate key="lang_btn_delete">Delete</translate>
            </b-button>
        </div>
        <div class="col-md-4 text-right">
            <b-button size="sm" variant="primary" v-b-modal.create_directory>
                <icon icon="folder"></icon>
                <translate key="lang_btn_new_folder">New Folder</translate>
            </b-button>
        </div>
    </div>
</template>
<script>
import _ from 'lodash';
import Icon from '~/components/Common/Icon';
import '~/vendor/sweetalert.js';

export default {
    name: 'station-media-toolbar',
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
        newPlaylistIsChecked() {
            return this.newPlaylist !== '';
        }
    },
    methods: {
        doImmediateQueue(e) {
            this.doBatch('immediate', this.$gettext('Files played immediately:'));
        },
        doQueue(e) {
            this.doBatch('queue', this.$gettext('Files queued for playback:'));
        },
        doReprocess(e) {
            this.doBatch('reprocess', this.$gettext('Files marked for reprocessing:'));
        },
        doDelete(e) {
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
