<template>
    <div>
        <b-card no-body>
            <b-card-header>
                <h2 class="card-title" v-translate>Streamer/DJ Accounts</h2>
            </b-card-header>
            <b-card-body body-class="card-padding-sm">
                <b-button variant="outline-primary" @click.prevent="doCreate">
                    <i class="material-icons" aria-hidden="true">add</i>
                    <translate>Add Playlist</translate>
                </b-button>
            </b-card-body>

            <div class="table-responsive table-responsive-lg">
                <data-table ref="datatable" id="station_streamers" paginated :fields="fields"
                            :api-url="listUrl">
                    <template v-slot:cell(actions)="row">
                        <b-button-group size="sm">
                            <b-button size="sm" variant="primary" @click.prevent="doEdit(row.item.links.self)">
                                <translate>Edit</translate>
                            </b-button>
                            <b-button size="sm" variant="default" @click.prevent="doShowBroadcasts(row.item.links.broadcasts)">
                                <translate>Broadcasts</translate>
                            </b-button>
                            <b-button size="sm" variant="danger" @click.prevent="doDelete(row.item.links.self)">
                                <translate>Delete</translate>
                            </b-button>
                        </b-button-group>
                    </template>
                </data-table>
            </div>
        </b-card>

        <edit-modal ref="editModal" :create-url="listUrl" @relist="relist"></edit-modal>
        <broadcasts-modal ref="broadcastsModal"></broadcasts-modal>
    </div>
</template>

<script>
    import DataTable from './components/DataTable';
    import axios from 'axios';
    import EditModal from './station_streamers/StreamerEditModal';
    import BroadcastsModal from './station_streamers/StreamerBroadcastsModal';

    export default {
        name: 'StationStreamers',
        components: { EditModal, BroadcastsModal, DataTable },
        props: {
            listUrl: String,
            filesUrl: String,
            stationTimeZone: String
        },
        data () {
            return {
                fields: [
                    { key: 'actions', label: this.$gettext('Actions'), sortable: false },
                    { key: 'streamer_username', label: this.$gettext('Username'), sortable: false },
                    { key: 'display_name', label: this.$gettext('Display Name'), sortable: false },
                    { key: 'comments', label: this.$gettext('Notes'), sortable: false }
                ]
            };
        },
        computed: {},
        methods: {
            relist () {
                this.$refs.datatable.refresh();
            },
            doCreate () {
                this.$refs.editModal.create();
            },
            doEdit (url) {
                this.$refs.editModal.edit(url);
            },
            doShowBroadcasts (url) {
                this.$refs.broadcastsModal.open(url);
            },
            doDelete (url) {
                let buttonText = this.$gettext('Delete');
                let buttonConfirmText = this.$gettext('Delete streamer?');

                swal({
                    title: buttonConfirmText,
                    buttons: [true, buttonText],
                    dangerMode: true
                }).then((value) => {
                    if (value) {
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