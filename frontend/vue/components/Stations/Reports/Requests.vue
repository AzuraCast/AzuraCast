<template>
    <div>
        <b-card no-body>
            <b-card-header header-bg-variant="primary-dark">
                <h2 class="card-title" key="lang_queue" v-translate>Song Requests</h2>
            </b-card-header>
            <b-tabs pills card>
                <b-tab v-for="tab in tabs" :key="tab.type" :active="activeType === tab.type" @click="setType(tab.type)"
                       :title="tab.title" no-body></b-tab>
            </b-tabs>

            <div class="card-actions" v-if="activeType === 'pending'">
                <b-button variant="outline-danger" @click="doClear()">
                    <icon icon="remove"></icon>
                    <translate key="lang_btn_clear_requests">Clear Pending Requests</translate>
                </b-button>
            </div>

            <data-table ref="datatable" id="station_queue" :fields="fields" :api-url="listUrlForType">
                <template #cell(timestamp)="row">
                    {{ formatTime(row.item.timestamp) }}
                </template>
                <template #cell(played_at)="row">
                    <span v-if="row.item.played_at === 0">
                        <translate key="lang_item_not_played">Not Played</translate>
                    </span>
                    <span v-else>
                        {{ formatTime(row.item.played_at) }}
                    </span>
                </template>
                <template #cell(song_title)="row">
                    <div v-if="row.item.track.title">
                        <b>{{ row.item.track.title }}</b><br>
                        {{ row.item.track.artist }}
                    </div>
                    <div v-else>
                        {{ row.item.track.text }}
                    </div>
                </template>
                <template #cell(ip)="row">
                    {{ row.item.ip }}
                </template>
                <template #cell(actions)="row">
                    <b-button-group>
                        <b-button v-if="row.item.played_at === 0" size="sm" variant="danger"
                                  @click.prevent="doDelete(row.item.links.delete)">
                            <translate key="lang_btn_delete">Delete</translate>
                        </b-button>
                    </b-button-group>
                </template>
            </data-table>
        </b-card>
    </div>
</template>

<script>
import DataTable from '~/components/Common/DataTable';
import Icon from "~/components/Common/Icon";
import {DateTime} from 'luxon';

export default {
    name: 'StationRequests',
    components: {DataTable, Icon},
    props: {
        listUrl: String,
        clearUrl: String,
        stationTimeZone: String
    },
    computed: {
        tabs() {
            return [
                {
                    type: 'pending',
                    title: this.$gettext('Pending Requests')
                },
                {
                    type: 'history',
                    title: this.$gettext('Request History')
                }
            ]
        },
        listUrlForType() {
            return this.listUrl + '?type=' + this.activeType;
        }
    },
    data() {
        return {
            activeType: 'pending',
            fields: [
                {key: 'timestamp', label: this.$gettext('Date Requested'), sortable: false},
                {key: 'played_at', label: this.$gettext('Date Played'), sortable: false},
                {key: 'song_title', isRowHeader: true, label: this.$gettext('Song Title'), sortable: false},
                {key: 'ip', label: this.$gettext('Requester IP'), sortable: false},
                {key: 'actions', label: this.$gettext('Actions'), sortable: false}
            ]
        }
    },
    methods: {
        setType(type) {
            this.activeType = type;
            this.relist();
        },
        relist() {
            this.$refs.datatable.refresh();
        },
        formatTime(time) {
            return DateTime.fromSeconds(time).setZone(this.stationTimeZone).toLocaleString(
                {...DateTime.DATETIME_MED, ...App.time_config}
            );
        },
        doDelete(url) {
            this.$confirmDelete({
                title: this.$gettext('Delete Request?'),
            }).then((result) => {
                if (result.value) {
                    this.$wrapWithLoading(
                        this.axios.delete(url)
                    ).then((resp) => {
                        this.$notifySuccess(resp.data.message);
                        this.relist();
                    });
                }
            });
        },
        doClear() {
            this.$confirmDelete({
                title: this.$gettext('Clear All Pending Requests?'),
                confirmButtonText: this.$gettext('Clear'),
            }).then((result) => {
                if (result.value) {
                    this.$wrapWithLoading(
                        this.axios.post(this.clearUrl)
                    ).then((resp) => {
                        this.$notifySuccess(resp.data.message);
                        this.relist();
                    });
                }
            });
        }
    }
};
</script>
