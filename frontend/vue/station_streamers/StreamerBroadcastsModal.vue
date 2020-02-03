<template>
    <b-modal id="streamer_broadcasts" size="lg" centered ref="modal" :title="langHeader">
        <template v-if="listUrl">
            <data-table ref="datatable" id="station_streamer_broadcasts" :show-toolbar="false"
                        :fields="fields" :api-url="listUrl">
                <template v-slot:cell(actions)="row">
                    <b-button-group size="sm" v-if="row.item.links_download">
                        <b-button size="sm" variant="primary" :href="row.item.links_download" target="_blank">
                            <translate>Download</translate>
                        </b-button>
                        <b-button size="sm" variant="danger" @click.prevent="doDelete(row.item.links_delete)">
                            <translate>Delete</translate>
                        </b-button>
                    </b-button-group>
                    <template v-else>&nbsp;</template>
                </template>
            </data-table>
        </template>
        <template v-slot:modal-footer>
            <b-button variant="default" @click="close">
                <translate>Close</translate>
            </b-button>
        </template>
    </b-modal>
</template>
<script>
    import DataTable from '../components/DataTable.vue';
    import axios from 'axios';

    export default {
        name: 'StreamerBroadcastsModal',
        components: { DataTable },
        data () {
            return {
                listUrl: null,
                fields: [
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
                                return this.$gettext('');
                            }
                            return moment.unix(value).format('lll');
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
            }
        },
        methods: {
            doDelete (url) {
                let buttonText = this.$gettext('Delete');
                let buttonConfirmText = this.$gettext('Delete broadcast?');

                swal({
                    title: buttonConfirmText,
                    buttons: [true, buttonText],
                    dangerMode: true
                }).then((value) => {
                    if (value) {
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
                this.listUrl = null;
                this.$refs.modal.hide();
            }
        }
    };
</script>