<template>
    <div>
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

                    <data-table ref="datatable" id="station_streamers" :show-toolbar="false" :fields="fields"
                                :api-url="listUrl">
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
                                <b-button size="sm" variant="default" @click.prevent="doShowBroadcasts(row.item.links.broadcasts)">
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
                              :locale="locale" @click="doCalendarClick"></schedule>
                </b-tab>
            </b-tabs>
        </b-card>

        <edit-modal ref="editModal" :create-url="listUrl" :station-time-zone="stationTimeZone" @relist="relist"></edit-modal>
        <broadcasts-modal ref="broadcastsModal"></broadcasts-modal>
    </div>
</template>

<script>
import DataTable from '~/components/Common/DataTable';
import EditModal from './Streamers/EditModal';
import BroadcastsModal from './Streamers/BroadcastsModal';
import Schedule from '~/components/Common/ScheduleView';
import Icon from '~/components/Common/Icon';
import handleAxiosError from '~/functions/handleAxiosError';
import confirmDelete from "~/functions/confirmDelete";

export default {
    name: 'StationStreamers',
    components: {Icon, EditModal, BroadcastsModal, DataTable, Schedule},
    props: {
        listUrl: String,
        scheduleUrl: String,
        filesUrl: String,
        locale: String,
        stationTimeZone: String
    },
    data () {
        return {
            fields: [
                {key: 'display_name', label: this.$gettext('Display Name'), sortable: false},
                {key: 'streamer_username', isRowHeader: true, label: this.$gettext('Username'), sortable: false},
                {key: 'comments', label: this.$gettext('Notes'), sortable: false},
                {key: 'actions', label: this.$gettext('Actions'), sortable: false, class: 'shrink'}
            ]
        };
    },
    computed: {
        langAccountListTab () {
            return this.$gettext('Account List');
        },
        langScheduleViewTab () {
            return this.$gettext('Schedule View');
        }
    },
    methods: {
        relist () {
            this.$refs.datatable.refresh();
        },
        doCreate () {
            this.$refs.editModal.create();
        },
        doCalendarClick (event) {
            this.doEdit(event.extendedProps.edit_url);
        },
        doEdit (url) {
            this.$refs.editModal.edit(url);
        },
        doShowBroadcasts (url) {
            this.$refs.broadcastsModal.open(url);
        },
        doDelete (url) {
            confirmDelete({
                title: this.$gettext('Delete Streamer?'),
                confirmButtonText: this.$gettext('Delete'),
            }).then((result) => {
                if (result.value) {
                    this.axios.delete(url).then((resp) => {
                        notify('<b>' + resp.data.message + '</b>', 'success');

                        this.relist();
                    }).catch((err) => {
                        handleAxiosError(err);
                    });
                }
            });
        }
    }
};
</script>
