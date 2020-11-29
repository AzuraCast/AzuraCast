<template>
    <b-modal id="streamer_broadcasts" size="lg" centered ref="modal" :title="langHeader">
        <template v-if="listUrl">
            <div class="card-header bg-primary-dark" v-show="now_playing_url != null">
                <inline-player ref="player"></inline-player>
            </div>

            <data-table ref="datatable" id="station_streamer_broadcasts" :show-toolbar="false"
                        :fields="fields" :api-url="listUrl">
                <template v-slot:cell(recording_links_download)="row">
                    <template v-if="row.item.recording_links_download">
                        <a class="file-icon btn-audio" href="#"
                           @click.prevent="playAudio(row.item.recording_links_download)" :title="langPlayPause">
                            <i class="material-icons" aria-hidden="true" v-if="now_playing_url === row.item.recording_links_download">pause_circle_filled</i>
                            <i class="material-icons" aria-hidden="true" v-else>play_circle_filled</i>
                        </a>
                        &nbsp;
                        <a class="name" :href="row.item.recording_links_download" target="_blank" :title="langDownload">
                            <i class="material-icons">cloud_download</i>
                        </a>
                    </template>
                    <template v-else>&nbsp;</template>
                </template>
                <template v-slot:cell(actions)="row">
                    <b-button-group size="sm" v-if="row.item.recording_links_download">
                        <b-button size="sm" variant="danger" @click.prevent="doDelete(row.item.recording_links_delete)">
                            <translate key="lang_btn_delete">Delete</translate>
                        </b-button>
                    </b-button-group>
                    <template v-else>&nbsp;</template>
                </template>
            </data-table>
        </template>
        <template v-slot:modal-footer>
            <b-button variant="default" @click="close">
                <translate key="lang_btn_close">Close</translate>
            </b-button>
        </template>
    </b-modal>
</template>
<script>
import DataTable from '../components/DataTable.vue';
import axios from 'axios';
import { formatFileSize } from '../inc/format_file_size';
import InlinePlayer from '../InlinePlayer';

export default {
    name: 'StreamerBroadcastsModal',
    components: { InlinePlayer, DataTable },
    data () {
        return {
            now_playing_url: null,
            listUrl: null,
            fields: [
                {
                    key: 'recording_links_download',
                    label: ' ',
                    sortable: false
                },
                {
                    key: 'timestampStart',
                    label: this.$gettext('Start Time'),
                    sortable: false,
                    formatter: (value, key, item) => {
                        return moment.unix(value).format('lll');
                    }
                },
                {
                    key: 'timestampEnd',
                    label: this.$gettext('End Time'),
                    sortable: false,
                    formatter: (value, key, item) => {
                        if (value === 0) {
                            return this.$gettext('Live');
                        }
                        return moment.unix(value).format('lll');
                    }
                },
                {
                    key: 'recording_size',
                    label: this.$gettext('Size'),
                    sortable: false,
                    formatter: (value, key, item) => {
                        if (!value) {
                            return '';
                        }

                        return formatFileSize(value);
                    }
                },
                {
                    key: 'actions',
                    label: this.$gettext('Actions'),
                    sortable: false
                }
            ]
        };
    },
    computed: {
        langHeader () {
            return this.$gettext('Streamer Broadcasts');
        },
        langPlayPause () {
            return this.$gettext('Play/Pause');
        },
        langDownload () {
            return this.$gettext('Download');
        }
    },
    mounted () {
        this.$eventHub.$on('player_stopped', () => {
            this.now_playing_url = null;
        });

        this.$eventHub.$on('player_playing', (url) => {
            this.now_playing_url = url;
        });
    },
    methods: {
        playAudio (url) {
            if (this.now_playing_url === url) {
                this.$refs.player.stop();
            } else {
                this.$refs.player.play(url);
            }
        },
        doDelete (url) {
            let buttonText = this.$gettext('Delete');
            let buttonConfirmText = this.$gettext('Delete broadcast?');

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

                        this.$refs.datatable.refresh();
                    }).catch((err) => {
                        console.error(err);
                        if (err.response.message) {
                            notify('<b>' + err.response.message + '</b>', 'danger');
                        }
                    });

                    this.$refs.datatable.refresh();
                }
            });
        },
        open (listUrl) {
            this.listUrl = listUrl;
            this.$refs.modal.show();
        },
        close () {
            this.$refs.player.stop();

            this.listUrl = null;
            this.$refs.modal.hide();
        }
    }
};
</script>