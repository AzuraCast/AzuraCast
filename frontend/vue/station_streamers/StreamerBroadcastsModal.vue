<template>
    <b-modal id="streamer_broadcasts" size="xl" centered ref="modal" :title="langHeader">
        <b-row>
            <b-col md="12">
                <data-table ref="datatable" id="station_media" :show-toolbar="false"
                            :selectable="false" :fields="fields"
                            :api-url="listUrl" :request-config="requestConfig">
                    <template v-slot:cell(directory)="row">
                        <div class="is_dir">
                            <span class="file-icon">
                                <i class="material-icons" aria-hidden="true">folder</i>
                            </span>

                            <a href="#" @click.prevent="enterDirectory(row.item.path)">
                                {{ row.item.name }}
                            </a>
                        </div>
                    </template>
                </data-table>
            </b-col>
        </b-row>
        <template v-slot:modal-footer>
            <b-button variant="default" @click="close">
                <translate>Close</translate>
            </b-button>
        </template>
    </b-modal>
</template>
<script>
    import DataTable from '../components/DataTable.vue';

    export default {
        name: 'StreamerBroadcastsModal',
        components: { DataTable },
        data () {
            return {
                listUrl: null,
                fields: [
                    { key: 'directory', label: this.$gettext('Directory'), sortable: false }
                ]
            };
        },
        computed: {
            langHeader () {
                return this.$gettext('Streamer Broadcasts');
            }
        },
        methods: {
            open (listUrl) {
                this.listUrl = listUrl;
                this.$refs.datatable.refresh();
                this.$refs.modal.show();
            },
            close () {
                this.listUrl = null;
                this.$refs.modal.hide();
            }
        }
    };
</script>