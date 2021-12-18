<template>
    <b-modal id="streamer_broadcasts" size="lg" centered ref="modal" :title="langHeader">
        <template v-if="listUrl">
            <div style="min-height: 40px;" class="flex-fill text-left bg-primary rounded mb-2">
                <inline-player ref="player"></inline-player>
            </div>

            <data-table ref="datatable" id="station_streamer_broadcasts" :show-toolbar="false"
                        :fields="fields" :api-url="listUrl">
                <template #cell(recording_links_download)="row">
                    <template v-if="row.item.recording_links_download">
                        <play-button class="file-icon" icon-class="outlined"
                                     :url="row.item.recording_links_download"></play-button>
                        &nbsp;
                        <a class="name" :href="row.item.recording_links_download" target="_blank" :title="langDownload">
                            <icon icon="cloud_download"></icon>
                        </a>
                    </template>
                    <template v-else>&nbsp;</template>
                </template>
                <template #cell(actions)="row">
                    <b-button-group size="sm" v-if="row.item.recording_links_download">
                        <b-button size="sm" variant="danger" @click.prevent="doDelete(row.item.recording_links_delete)">
                            <translate key="lang_btn_delete">Delete</translate>
                        </b-button>
                    </b-button-group>
                    <template v-else>&nbsp;</template>
                </template>
            </data-table>
        </template>
        <template #modal-footer>
            <b-button variant="default" @click="close">
                <translate key="lang_btn_close">Close</translate>
            </b-button>
        </template>
    </b-modal>
</template>
<script>
import DataTable from '~/components/Common/DataTable.vue';
import formatFileSize from '~/functions/formatFileSize.js';
import InlinePlayer from '~/components/InlinePlayer';
import Icon from '~/components/Common/Icon';
import PlayButton from "~/components/Common/PlayButton";
import {DateTime} from 'luxon';
import '~/vendor/sweetalert.js';

export default {
    name: 'StreamerBroadcastsModal',
    components: {PlayButton, Icon, InlinePlayer, DataTable},
    data() {
        return {
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
                        return DateTime.fromSeconds(value).toLocaleString(DateTime.DATETIME_MED);
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
                        return DateTime.fromSeconds(value).toLocaleString(DateTime.DATETIME_MED);
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
    methods: {
        doDelete (url) {
            this.$confirmDelete({
                title: this.$gettext('Delete Broadcast?')
            }).then((result) => {
                if (result.value) {
                    this.axios.delete(url).then((resp) => {
                        this.$notifySuccess(resp.data.message);
                        this.$refs.datatable.refresh();
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
