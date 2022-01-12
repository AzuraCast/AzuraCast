<template>
    <div>
        <b-card no-body>
            <b-card-header header-bg-variant="primary-dark">
                <h2 class="card-title" key="lang_storage_locations" v-translate>Storage Locations</h2>
            </b-card-header>
            <b-tabs pills card lazy>
                <b-tab :active="activeType === 'station_media'" @click="setType('station_media')" :title="langStationMediaTab" no-body></b-tab>
                <b-tab :active="activeType === 'station_recordings'" @click="setType('station_recordings')" :title="langStationRecordingsTab" no-body></b-tab>
                <b-tab :active="activeType === 'station_podcasts'" @click="setType('station_podcasts')" :title="langStationPodcastsTab" no-body></b-tab>
                <b-tab :active="activeType === 'backup'" @click="setType('backup')" :title="langBackupsTab" no-body></b-tab>
            </b-tabs>

            <b-card-body body-class="card-padding-sm">
                <b-button variant="outline-primary" @click.prevent="doCreate">
                    <icon icon="add"></icon>
                    <translate key="lang_add_playlist">Add Storage Location</translate>
                </b-button>
            </b-card-body>

            <data-table ref="datatable" id="admin_storage_locations" :show-toolbar="false" :fields="fields" :responsive="false"
                        :api-url="listUrlForType">
                <template #cell(actions)="row">
                    <b-button-group size="sm">
                        <b-button size="sm" variant="primary" @click.prevent="doEdit(row.item.links.self)">
                            <translate key="lang_btn_edit">Edit</translate>
                        </b-button>
                        <b-button size="sm" variant="danger" @click.prevent="doDelete(row.item.links.self)">
                            <translate key="lang_btn_delete">Delete</translate>
                        </b-button>
                    </b-button-group>
                </template>
                <template #cell(adapter)="row">
                    <h5 class="m-0">{{ getAdapterName(row.item.adapter) }}</h5>
                    <p class="card-text">{{ row.item.uri }}</p>
                </template>
                <template #cell(space)="row">
                    <template v-if="row.item.storageAvailable">
                        <b-progress :value="row.item.storageUsedPercent" show-progress height="15px" class="mb-1"
                                    :variant="getProgressVariant(row.item.storageUsedPercent)">
                        </b-progress>

                        {{ getSpaceUsed(row.item) }}
                    </template>
                    <template v-else>
                        {{ getSpaceUsed(row.item) }}
                    </template>
                </template>
                <template #cell(stations)="row">
                    {{ row.item.stations.join(', ') }}
                </template>
            </data-table>
        </b-card>

        <edit-modal ref="editModal" :create-url="listUrl" :type="activeType" @relist="relist"></edit-modal>
    </div>
</template>

<script>
import DataTable from '~/components/Common/DataTable';
import EditModal from './StorageLocations/EditModal';
import Icon from '~/components/Common/Icon';

export default {
    name: 'AdminStorageLocations',
    components: {Icon, EditModal, DataTable},
    props: {
        listUrl: String
    },
    data() {
        return {
            activeType: 'station_media',
            fields: [
                {key: 'adapter', label: this.$gettext('Adapter'), sortable: false},
                {key: 'space', label: this.$gettext('Space Used'), class: 'text-nowrap', sortable: false},
                {key: 'stations', label: this.$gettext('Station(s)'), sortable: false},
                {key: 'actions', label: this.$gettext('Actions'), class: 'shrink', sortable: false}
            ]
        };
    },
    computed: {
        langStationMediaTab () {
            return this.$gettext('Station Media');
        },
        langStationRecordingsTab () {
            return this.$gettext('Station Recordings');
        },
        langStationPodcastsTab () {
            return this.$gettext('Station Podcasts');
        },
        langBackupsTab () {
            return this.$gettext('Backups');
        },
        listUrlForType () {
            return this.listUrl + '?type=' + this.activeType;
        }
    },
    methods: {
        setType (type) {
            this.activeType = type;
            this.relist();
        },
        getAdapterName (adapter) {
            switch (adapter) {
                case 'local':
                    return this.$gettext('Local');

                case 's3':
                    return this.$gettext('Remote: S3 Compatible');

                case 'dropbox':
                    return this.$gettext('Remote: Dropbox');
            }
        },
        getSpaceUsed(item) {
            return (item.storageAvailable)
                ? item.storageUsed + ' / ' + item.storageAvailable
                : item.storageUsed;
        },
        getProgressVariant(percent) {
            if (percent > 85) {
                return 'danger';
            } else if (percent > 65) {
                return 'warning';
            } else {
                return 'default';
            }
        },
        relist() {
            this.$refs.datatable.refresh();
        },
        doCreate() {
            this.$refs.editModal.create();
        },
        doEdit(url) {
            this.$refs.editModal.edit(url);
        },
        doModify(url) {
            this.$notify(this.$gettext('Applying changes...'), {
                variant: 'warning'
            });

            this.axios.put(url).then((resp) => {
                this.$notifySuccess(resp.data.message);
                this.relist();
            });
        },
        doDelete (url) {
            this.$confirmDelete({
                title: this.$gettext('Delete Storage Location?'),
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
