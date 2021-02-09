<template>
    <b-card no-body>
        <b-card-header header-bg-variant="primary-dark">
            <h2 class="card-title" key="lang_queue" v-translate>Upcoming Song Queue</h2>
        </b-card-header>
        <data-table ref="datatable" id="station_queue" :show-toolbar="false" :fields="fields" :api-url="listUrl">
            <template v-slot:cell(actions)="row">
                <div v-if="row.item.links.self">
                    <b-button size="sm" variant="danger" @click.prevent="doDelete(row.item.links.self)">
                        <translate key="lang_btn_delete">Delete</translate>
                    </b-button>
                </div>
            </template>
            <template v-slot:cell(song_title)="row">
                <div v-if="row.item.autodj_custom_uri">
                    {{ row.item.autodj_custom_uri }}
                </div>
                <div v-else-if="row.item.song.title">
                    <b>{{ row.item.song.title }}</b><br>
                    {{ row.item.song.artist }}
                </div>
                <div v-else>
                    {{ row.item.song.text }}
                </div>
            </template>
            <template v-slot:cell(cued_at)="row">
                {{ formatTime(row.item.cued_at) }}
            </template>
            <template v-slot:cell(source)="row">
                <div v-if="row.item.is_request">
                    <translate key="lang_source_request">Listener Request</translate>
                </div>
                <div v-else-if="row.item.playlist">
                    <translate key="lang_source_playlist">Playlist: </translate>
                    {{ row.item.playlist }}
                </div>
            </template>
        </data-table>
    </b-card>
</template>

<script>
import DataTable from './components/DataTable';
import axios from 'axios';

export default {
    name: 'StationPlaylists',
    components: { DataTable },
    props: {
        listUrl: String,
        locale: String,
        stationTimeZone: String
    },
    data () {
        return {
            fields: [
                { key: 'actions', label: this.$gettext('Actions'), sortable: false },
                { key: 'song_title', isRowHeader: true, label: this.$gettext('Song Title'), sortable: false },
                { key: 'cued_at', label: this.$gettext('Cued On'), sortable: false },
                { key: 'source', label: this.$gettext('Source'), sortable: false }
            ]
        };
    },
    computed: {},
    mounted () {
        moment.relativeTimeThreshold('ss', 1);
        moment.relativeTimeRounding(function (value) {
            return Math.round(value * 10) / 10;
        });
    },
    methods: {
        formatTime (time) {
            return moment.unix(time).tz(this.stationTimeZone).format('lll');
        },
        doDelete (url) {
            let buttonText = this.$gettext('Delete');
            let buttonConfirmText = this.$gettext('Delete queue item?');

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
                }
            });
        }
    }
};
</script>
