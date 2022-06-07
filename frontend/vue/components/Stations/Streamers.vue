<template>
    <div class="row">
        <div class="col-md-8">
            <b-card no-body>
                <b-card-header header-bg-variant="primary-dark">
                    <b-row class="align-items-center">
                        <b-col md="6">
                            <h2 class="card-title" key="lang_title" v-translate>Streamer/DJ Accounts</h2>
                        </b-col>
                        <b-col md="6" class="text-right text-muted">
                            <translate key="lang_station_tz" :translate-params="{ tz: stationTimeZone }">This station's time zone is currently %{tz}.</translate>
                        </b-col>
                    </b-row>
                </b-card-header>

                <b-tabs pills card lazy>
                    <b-tab :title="langAccountListTab" no-body>
                        <b-card-body body-class="card-padding-sm">
                            <b-button variant="outline-primary" @click.prevent="doCreate">
                                <icon icon="add"></icon>
                                <translate key="lang_add_streamer">Add Streamer</translate>
                            </b-button>
                        </b-card-body>

                        <data-table ref="datatable" id="station_streamers" :fields="fields"
                                    :api-url="listUrl">
                            <template #cell(art)="row">
                                <album-art :src="row.item.art"></album-art>
                            </template>
                            <template #cell(streamer_username)="row">
                                <code>{{ row.item.streamer_username }}</code>
                                <div>
                                <span class="badge badge-danger" v-if="!row.item.is_active">
                                    <translate key="lang_disabled">Disabled</translate>
                                </span>
                                </div>
                            </template>
                            <template #cell(actions)="row">
                                <b-button-group size="sm">
                                    <b-button size="sm" variant="primary" @click.prevent="doEdit(row.item.links.self)">
                                        <translate key="lang_btn_edit">Edit</translate>
                                    </b-button>
                                    <b-button size="sm" variant="default"
                                              @click.prevent="doShowBroadcasts(row.item.links.broadcasts)">
                                        <translate key="lang_btn_broadcasts">Broadcasts</translate>
                                    </b-button>
                                    <b-button size="sm" variant="danger" @click.prevent="doDelete(row.item.links.self)">
                                        <translate key="lang_btn_delete">Delete</translate>
                                    </b-button>
                                </b-button-group>
                            </template>
                        </data-table>
                    </b-tab>
                    <b-tab :title="langScheduleViewTab" no-body>
                        <schedule ref="schedule" :schedule-url="scheduleUrl" :station-time-zone="stationTimeZone"
                                  @click="doCalendarClick"></schedule>
                    </b-tab>
                </b-tabs>
            </b-card>
        </div>
        <div class="col-md-4">
            <connection-info :connection-info="connectionInfo"></connection-info>
        </div>

        <edit-modal ref="editModal" :create-url="listUrl" :station-time-zone="stationTimeZone"
                    :new-art-url="newArtUrl" @relist="relist"></edit-modal>
        <broadcasts-modal ref="broadcastsModal"></broadcasts-modal>
    </div>
</template>

<script>
import DataTable from '~/components/Common/DataTable';
import EditModal from './Streamers/EditModal';
import BroadcastsModal from './Streamers/BroadcastsModal';
import Schedule from '~/components/Common/ScheduleView';
import Icon from '~/components/Common/Icon';
import ConnectionInfo from "./Streamers/ConnectionInfo";
import AlbumArt from "~/components/Common/AlbumArt";

export default {
    name: 'StationStreamers',
    components: {AlbumArt, ConnectionInfo, Icon, EditModal, BroadcastsModal, DataTable, Schedule},
    props: {
        listUrl: String,
        newArtUrl: String,
        scheduleUrl: String,
        stationTimeZone: String,
        connectionInfo: Object
    },
    data() {
        return {
            fields: [
                {key: 'art', label: this.$gettext('Art'), sortable: false, class: 'shrink pr-0'},
                {key: 'display_name', label: this.$gettext('Display Name'), sortable: true},
                {key: 'streamer_username', isRowHeader: true, label: this.$gettext('Username'), sortable: true},
                {key: 'comments', label: this.$gettext('Notes'), sortable: false},
                {key: 'actions', label: this.$gettext('Actions'), sortable: false, class: 'shrink'}
            ]
        };
    },
    computed: {
        langAccountListTab() {
            return this.$gettext('Account List');
        },
        langScheduleViewTab() {
            return this.$gettext('Schedule View');
        }
    },
    methods: {
        relist() {
            this.$refs.datatable.refresh();
        },
        doCreate() {
            this.$refs.editModal.create();
        },
        doCalendarClick(event) {
            this.doEdit(event.extendedProps.edit_url);
        },
        doEdit(url) {
            this.$refs.editModal.edit(url);
        },
        doShowBroadcasts(url) {
            this.$refs.broadcastsModal.open(url);
        },
        doDelete(url) {
            this.$confirmDelete({
                title: this.$gettext('Delete Streamer?'),
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
        }
    }
};
</script>
