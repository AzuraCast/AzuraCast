<template>
    <div class="row pt-4" id="app-toolbar">
        <div class="col-md-8">
            <div class="btn-group dropdown allow-focus">
                <b-dropdown size="sm" variant="primary" ref="setPlaylistsDropdown" v-b-tooltip.hover
                            :title="langPlaylistDropdown">
                    <template v-slot:button-content>
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
                <template v-slot:button-content>
                    <icon icon="more_horiz"></icon>
                    {{ langMore }}
                </template>
                <b-dropdown-item @click="doQueue" v-b-tooltip.hover :title="langQueue">
                    <translate key="lang_btn_queue">Queue</translate>
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
import axios from 'axios';
import _ from 'lodash';
import Icon from '../../Common/Icon';
import handleAxiosError from '../../Function/handleAxiosError';

export default {
    name: 'station-media-toolbar',
    components: { Icon },
    props: {
        currentDirectory: String,
        selectedItems: Object,
        playlists: Array,
        batchUrl: String
    },
    data () {
        return {
            checkedPlaylists: [],
            newPlaylist: ''
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
        langPlaylistDropdown () {
            return this.$gettext('Set or clear playlists from the selected media');
        },
        langNewPlaylist () {
            return this.$gettext('New Playlist');
        },
        langMore () {
            return this.$gettext('More');
        },
        langQueue () {
            return this.$gettext('Queue the selected media to play next');
        },
        langReprocess () {
            return this.$gettext('Analyze and reprocess the selected media');
        },
        langErrors () {
            return this.$gettext('The request could not be processed.');
        },
        newPlaylistIsChecked () {
            return this.newPlaylist !== '';
        }
    },
    methods: {
        doQueue (e) {
            this.doBatch('queue', this.$gettext('Files queued for playback:'));
        },
        doReprocess (e) {
            this.doBatch('reprocess', this.$gettext('Files marked for reprocessing:'));
        },
        doDelete (e) {
            let buttonText = this.$gettext('Delete');
            let buttonConfirmText = this.$gettext('Delete %{ num } media files?');

            let numFiles = this.selectedItems.all.length;

            Swal.fire({
                title: this.$gettextInterpolate(buttonConfirmText, { num: numFiles }),
                confirmButtonText: buttonText,
                confirmButtonColor: '#e64942',
                showCancelButton: true,
                focusCancel: true
            }).then((result) => {
                if (result.value) {
                    this.doBatch('delete', this.$gettext('Files removed:'));
                }
            });
        },
        doBatch (action, notifyMessage) {
            if (this.selectedItems.all.length) {
                this.notifyPending();

                axios.put(this.batchUrl, {
                    'do': action,
                    'current_directory': this.currentDirectory,
                    'files': this.selectedItems.files,
                    'dirs': this.selectedItems.directories
                }).then((resp) => {
                    if (resp.data.success) {
                        let allItemNames = _.map(this.selectedItems.all, 'path_short');
                        notify('<b>' + notifyMessage + '</b><br>' + allItemNames.join('<br>'), 'success');
                    } else {
                        notify('<b>' + this.langErrors + '</b><br>' + resp.data.errors.join('<br>'), 'danger');
                    }

                    this.$emit('relist');
                }).catch((err) => {
                    handleAxiosError(err);
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
                this.notifyPending();

                axios.put(this.batchUrl, {
                    'do': 'playlist',
                    'playlists': this.checkedPlaylists,
                    'new_playlist_name': this.newPlaylist,
                    'currentDirectory': this.currentDirectory,
                    'files': this.selectedItems.files,
                    'dirs': this.selectedItems.directories
                }).then((resp) => {
                    if (resp.data.success) {
                        if (resp.data.record) {
                            this.$emit('add-playlist', resp.data.record);
                        }

                        let notifyMessage = (this.checkedPlaylists.length > 0)
                            ? this.$gettext('Playlists updated for selected files:')
                            : this.$gettext('Playlists cleared for selected files:');

                        let allItemNames = _.map(this.selectedItems.all, 'path_short');
                        notify('<b>' + notifyMessage + '</b><br>' + allItemNames.join('<br>'), 'success');

                        this.checkedPlaylists = [];
                        this.newPlaylist = '';
                    } else {
                        notify(resp.data.errors.join('<br>'), 'danger');
                    }

                    this.$emit('relist');
                }).catch((err) => {
                    handleAxiosError(err);
                });
            } else {
                this.notifyNoFiles();
            }
        },
        notifyPending () {
            notify('<b>' + this.$gettext('Applying changes...') + '</b>', 'warning', {
                delay: 3000
            });
        },
        notifyNoFiles () {
            notify('<b>' + this.$gettext('No files selected.') + '</b>', 'danger');
        }
    }
};
</script>
