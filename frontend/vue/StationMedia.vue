<template>
    <div>
        <div class="card-body">
            <breadcrumb :current-directory="currentDirectory" @change-directory="changeDirectory"></breadcrumb>

            <file-upload :upload-url="uploadUrl" :search-phrase="searchPhrase" :valid-mime-types="validMimeTypes"
                         :current-directory="currentDirectory" @relist="onTriggerRelist"></file-upload>

            <media-toolbar :selected-files="selectedFiles" :selected-dirs="selectedDirs"
                           :batch-url="batchUrl" :current-directory="currentDirectory"
                           :initial-playlists="initialPlaylists" @relist="onTriggerRelist"></media-toolbar>
        </div>

        <data-table ref="datatable" id="station_media" selectable paginated select-fields
                    @row-selected="onRowSelected" @refreshed="onRefreshed" :fields="fields" :api-url="listUrl"
                    :request-config="requestConfig">
            <template v-slot:cell(name)="row">
                <div :class="{ is_dir: row.item.is_dir, is_file: !row.item.is_dir }">
                    <a :href="row.item.media_art" class="album-art float-right pl-3" target="_blank"
                       v-if="row.item.media_art" data-fancybox="gallery">
                        <img class="media_manager_album_art" :alt="langAlbumArt" :src="row.item.media_art">
                    </a>
                    <template v-if="row.item.media_is_playable">
                        <a class="file-icon btn-audio" href="#" :data-url="row.item.media_play_url"
                           @click.prevent="playAudio(row.item.media_play_url)" :title="langPlayPause">
                            <i class="material-icons" aria-hidden="true">play_circle_filled</i>
                        </a>
                    </template>
                    <template v-else>
                        <span class="file-icon">
                            <i class="material-icons" aria-hidden="true" v-if="row.item.is_dir">folder</i>
                            <i class="material-icons" aria-hidden="true" v-else>note</i>
                        </span>
                    </template>
                    <template v-if="row.item.is_dir">
                        <a class="name" href="#" @click.prevent="changeDirectory(row.item.path)"
                           :title="row.item.name">
                            {{ row.item.text }}
                        </a>
                    </template>
                    <template v-else>
                        <a class="name" :href="row.item.media_play_url" target="_blank" :title="row.item.name">
                            {{ row.item.media_name }}
                        </a>
                    </template>
                    <br>
                    <small v-if="row.item.is_dir" key="lang_dir" v-translate>Directory</small>
                    <small v-else>{{ row.item.text }}</small>
                </div>
            </template>
            <template v-slot:cell(media_genre)="row">
                {{ row.item.media_genre }}
            </template>
            <template v-slot:cell(media_length)="row">
                {{ row.item.media_length_text }}
            </template>
            <template v-slot:cell(size)="row">
                <template v-if="!row.item.size">&nbsp;</template>
                <template v-else>
                    {{ formatFileSize(row.item.size) }}
                </template>
            </template>
            <template v-slot:cell(playlists)="row">
                <template v-for="(playlist, index) in row.item.media_playlists">
                    <a class="btn-search" href="#" @click.prevent="filter('playlist:'+playlist)"
                       :title="langPlaylistSelect">{{ playlist }}</a>
                    <span v-if="index+1 < row.item.media_playlists.length">, </span>
                </template>
            </template>
            <template v-slot:cell(commands)="row">
                <template v-if="row.item.media_can_edit">
                    <b-button size="sm" variant="primary"
                              @click.prevent="edit(row.item.media_edit_url, row.item.media_art_url, row.item.media_play_url, row.item.media_waveform_url)">
                        {{ langEditButton }}
                    </b-button>
                </template>
                <template v-else-if="row.item.can_rename">
                    <b-button size="sm" variant="primary" @click.prevent="rename(row.item.path)">
                        {{ langRenameButton }}
                    </b-button>
                </template>
                <template v-else>&nbsp;</template>
            </template>
        </data-table>

        <new-directory-modal :current-directory="currentDirectory" :mkdir-url="mkdirUrl"
                             @relist="onTriggerRelist">
        </new-directory-modal>

        <move-files-modal :selected-files="selectedFiles" :selected-dirs="selectedDirs"
                          :current-directory="currentDirectory" :batch-url="batchUrl"
                          :list-directories-url="listDirectoriesUrl" @relist="onTriggerRelist">
        </move-files-modal>

        <rename-modal :rename-url="renameUrl" ref="renameModal" @relist="onTriggerRelist">
        </rename-modal>

        <edit-modal ref="editModal" :custom-fields="customFields" @relist="onTriggerRelist"></edit-modal>
    </div>
</template>

<style lang="scss">
img.media_manager_album_art {
    width: 40px;
    height: auto;
    border-radius: 5px;
}
</style>

<script>
import DataTable from './components/DataTable';
import MediaToolbar from './station_media/MediaToolbar';
import Breadcrumb from './station_media/MediaBreadcrumb';
import FileUpload from './station_media/MediaFileUpload';
import NewDirectoryModal from './station_media/MediaNewDirectoryModal';
import MoveFilesModal from './station_media/MediaMoveFilesModal';
import RenameModal from './station_media/MediaRenameModal';
import EditModal from './station_media/MediaEditModal';
import { formatFileSize } from './inc/format_file_size';
import _ from 'lodash';

export default {
    components: {
        EditModal,
        RenameModal,
        MoveFilesModal,
        NewDirectoryModal,
        FileUpload,
        MediaToolbar,
        DataTable,
        Breadcrumb
    },
    props: {
        listUrl: {
            type: String,
            required: true
        },
        batchUrl: {
            type: String,
            required: true
        },
        uploadUrl: {
            type: String,
            required: true
        },
        listDirectoriesUrl: {
            type: String,
            required: true
        },
        mkdirUrl: {
            type: String,
            required: true
        },
        renameUrl: {
            type: String,
            required: true
        },
        initialPlaylists: {
            type: Array,
            required: false,
            default: () => []
        },
        customFields: {
            type: Array,
            required: false,
            default: () => []
        },
        validMimeTypes: {
            type: Array,
            required: false,
            default: () => []
        }
    },
    data () {
        let fields = [
            { key: 'name', isRowHeader: true, label: this.$gettext('Name'), sortable: true },
            { key: 'media_title', label: this.$gettext('Title'), sortable: true, selectable: true, visible: false },
            {
                key: 'media_artist',
                label: this.$gettext('Artist'),
                sortable: true,
                selectable: true,
                visible: false
            },
            { key: 'media_album', label: this.$gettext('Album'), sortable: true, selectable: true, visible: false },
            { key: 'media_genre', label: this.$gettext('Genre'), sortable: true, selectable: true, visible: false },
            { key: 'media_length', label: this.$gettext('Length'), sortable: true, selectable: true, visible: true }
        ];

        _.forEach(this.customFields.slice(), (field) => {
            fields.push({
                key: field.display_key,
                label: field.label,
                sortable: true,
                selectable: true,
                visible: false
            });
        });

        fields.push(
            { key: 'size', label: this.$gettext('Size'), sortable: true, selectable: true, visible: true },
            {
                key: 'mtime',
                label: this.$gettext('Modified'),
                sortable: true,
                formatter: (value, key, item) => {
                    if (!value) {
                        return '';
                    }
                    return moment.unix(value).format('lll');
                },
                selectable: true,
                visible: true
            },
            {
                key: 'playlists',
                label: this.$gettext('Playlists'),
                sortable: true,
                selectable: true,
                visible: true
            },
            { key: 'commands', label: this.$gettext('Actions'), sortable: false }
        );

        return {
            fields: fields,
            selectedFiles: [],
            selectedDirs: [],
            currentDirectory: '',
            searchPhrase: null
        };
    },
    mounted () {
        // Load directory from URL hash, if applicable.
        let urlHash = decodeURIComponent(window.location.hash.substr(1).replace(/\+/g, '%20'));

        if (urlHash.substr(0, 9) === 'playlist:') {
            window.location.hash = '';
            this.filter(urlHash);
        } else if (urlHash !== '') {
            this.changeDirectory(urlHash);
        }
    },
    computed: {
        langAlbumArt () {
            return this.$gettext('Album Art');
        },
        langRenameButton () {
            return this.$gettext('Rename');
        },
        langEditButton () {
            return this.$gettext('Edit');
        },
        langPlayPause () {
            return this.$gettext('Play/Pause');
        },
        langPlaylistSelect () {
            return this.$gettext('View tracks in playlist');
        }
    },
    methods: {
        formatFileSize (size) {
            return formatFileSize(size);
        },
        onRowSelected (items) {
            this.selectedFiles = _.map(_.filter(items, (row) => {
                return !row.is_dir;
            }), 'name');
            this.selectedDirs = _.map(_.filter(items, (row) => {
                return row.is_dir;
            }), 'name');
        },
        onRefreshed () {
            this.$eventHub.$emit('refreshed');
        },
        onTriggerNavigate () {
            this.$refs.datatable.navigate();
        },
        onTriggerRelist () {
            this.$refs.datatable.relist();
        },
        playAudio (url) {
            this.$eventHub.$emit('player_toggle', url);
        },
        changeDirectory (newDir) {
            window.location.hash = newDir;

            this.currentDirectory = newDir;
            this.onTriggerNavigate();
        },
        filter (newFilter) {
            this.$refs.datatable.setFilter(newFilter);
        },
        onFiltered (newFilter) {
            this.searchPhrase = newFilter;
        },
        rename (path) {
            this.$refs.renameModal.open(path);
        },
        edit (recordUrl, albumArtUrl, audioUrl, waveformUrl) {
            this.$refs.editModal.open(recordUrl, albumArtUrl, audioUrl, waveformUrl);
        },
        requestConfig (config) {
            config.params.currentDirectory = this.currentDirectory;
            return config;
        }
    }
};
</script>
