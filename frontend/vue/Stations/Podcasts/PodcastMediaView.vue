<template>
    <div>
        <div class="card-body">
            <breadcrumb :current-directory="currentDirectory" @change-directory="changeDirectory"></breadcrumb>

            <file-upload :upload-url="uploadUrl" :search-phrase="searchPhrase"
                         :current-directory="currentDirectory" @relist="onTriggerRelist"></file-upload>
        </div>

        <data-table ref="datatable" id="station_podcast_media" paginated @refreshed="onRefreshed" :fields="fields" :api-url="listUrl">
            <template v-slot:cell(art_src)="row">
                <template v-if="row.item.is_dir">
                    <span class="file-icon">
                        <i class="material-icons" aria-hidden="true">folder</i>
                    </span>
                </template>
                <template v-else>
                    <album-art :src="row.item.art"></album-art>
                </template>
            </template>
            <template v-slot:cell(original_name)="row">
                <div :class="{ is_dir: row.item.is_dir, is_file: !row.item.is_dir }">
                    <template v-if="row.item.is_dir">
                        <a class="original_name" href="#" @click.prevent="changeDirectory(row.item.path)"
                           :title="row.item.original_name">
                            {{ row.item.path }}
                        </a>
                    </template>
                    <template v-else>
                        <a class="original_name" :href="row.item.links.play" target="_blank" :title="row.item.original_name">
                            {{ row.item.original_name }}
                        </a>
                    </template>
                    <br>
                    <small v-if="row.item.is_dir" key="lang_dir" v-translate>Directory</small>
                    <small v-else>{{ row.item.path }}</small>
                </div>
            </template>
            <template v-slot:cell(length)="row">
                {{ row.item.length_text }}
            </template>
            <template v-slot:cell(size)="row">
                <template v-if="!row.item.size">&nbsp;</template>
                <template v-else>
                    {{ formatFileSize(row.item.size) }}
                </template>
            </template>
            <template v-slot:cell(actions)="row">
                <b-button-group size="sm">
                    <b-button size="sm" variant="primary" @click.prevent="doAssign(row.item)">
                        <translate key="lang_btn_delete">Assign</translate>
                    </b-button>
                    <b-button size="sm" variant="danger" @click.prevent="doDelete(row.item.links.delete)">
                        <translate key="lang_btn_delete">Delete</translate>
                    </b-button>
                </b-button-group>
            </template>
        </data-table>

        <assign-modal ref="assignEpisodeModal" :list-url="assignableEpisodesUrl" :station-time-zone="stationTimeZone"
            @relist="relist"></assign-modal>
    </div>
</template>

<style lang="scss">
    #station_podcast_media table thead tr th:first-of-type,
    #station_podcast_media table tbody tr td:first-of-type {
        padding-right: 0;
        width: 40px;
    }
</style>

<script>
import DataTable from '../../Common/DataTable';
import Breadcrumb from './PodcastMediaBreadcrumb';
import FileUpload from './PodcastMediaFileUpload';
import AssignModal from './PodcastMediaAssignEpisodeModal';
import formatFileSize from '../../Function/FormatFileSize.js';
import axios from 'axios';
import AlbumArt from '../../Common/AlbumArt';

export default {
    components: {
        AlbumArt,
        DataTable,
        Breadcrumb,
        FileUpload,
        AssignModal
    },
    props: {
        listUrl: String,
        uploadUrl: String,
        assignableEpisodesUrl: String,
        stationTimeZone: String
    },
    data () {
        return {
            fields: [
                { key: 'art_src', label: this.$gettext('Art'), sortable: false },
                { key: 'original_name', label: this.$gettext('Name'), sortable: true },
                { key: 'length_text', label: this.$gettext('Length'), sortable: true, selectable: true, visible: true },
                { key: 'size', label: this.$gettext('Size'), sortable: true, selectable: true, visible: true },
                {
                    key: 'modified_at',
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
                { key: 'actions', label: this.$gettext('Actions'), sortable: false }
            ],
            currentDirectory: '',
            searchPhrase: null
        };
    },
    mounted () {
        // Load directory from URL hash, if applicable.
        let urlHash = decodeURIComponent(window.location.hash.substr(1).replace(/\+/g, '%20'));

        this.changeDirectory(urlHash);
    },
    computed: {
        langPodcastMediaArt () {
            return this.$gettext('Media Art');
        }
    },
    methods: {
        formatFileSize (size) {
            return formatFileSize(size);
        },
        relist () {
            if (this.$refs.datatable) {
                this.$refs.datatable.refresh();
            }
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
            changeDirectory (newDir) {
                window.location.hash = newDir;

                this.currentDirectory = newDir;
                this.onTriggerNavigate();
            },
            doAssign (podcastMedia) {
                this.$refs.assignEpisodeModal.assign(podcastMedia);
            },
            doDelete (url) {
                let buttonText = this.$gettext('Delete');
                let buttonConfirmText = this.$gettext('Delete File?');

                Swal.fire({
                    title: buttonConfirmText,
                    confirmButtonText: buttonText,
                    confirmButtonColor: '#e64942',
                    showCancelButton: true,
                    focusCancel: true
                }).then((result) => {
                    if (result.value) {
                        axios.delete(url).then((resp) => {
                            notify('<b>' + resp.data.message + '</b>', 'success');

                            this.relist();
                        }).catch((err) => {
                            console.error(err);
                            if (err.response.message) {
                                notify('<b>' + err.response.message + '</b>', 'danger');
                            }
                        });
                    }
                });
            }
        }
    };
</script>
