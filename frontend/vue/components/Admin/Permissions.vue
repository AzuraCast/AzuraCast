<template>
    <div>
        <b-card no-body>
            <b-card-header header-bg-variant="primary-dark">
                <h2 class="card-title" key="lang_title" v-translate>Roles & Permissions</h2>
            </b-card-header>

            <info-card>
                <p class="card-text">
                    <translate key="lang_card_info">AzuraCast uses a role-based access control system. Roles are given permissions to certain sections of the site, then users are assigned into those roles.</translate>
                </p>
            </info-card>

            <b-card-body body-class="card-padding-sm">
                <b-button variant="outline-primary" @click.prevent="doCreate">
                    <icon icon="add"></icon>
                    <translate key="lang_add_btn">Add Role</translate>
                </b-button>
            </b-card-body>

            <data-table ref="datatable" id="permissions" paginated :fields="fields" :api-url="listUrl">
                <template #cell(permissions)="row">
                    <div v-if="row.item.permissions.global.length > 0">
                        <translate key="lang_permissions_global">Global</translate>
                        :
                        {{ getGlobalPermissionNames(row.item.permissions.global).join(', ') }}
                    </div>
                    <div v-for="(permissions, stationId) in row.item.permissions.station" :key="stationId">
                        <b>{{ getStationName(stationId) }}</b>:
                        {{ getStationPermissionNames(permissions).join(', ') }}
                    </div>
                </template>
                <template #cell(actions)="row">
                    <b-button-group size="sm" v-if="!row.item.is_super_admin">
                        <b-button size="sm" variant="primary" @click.prevent="doEdit(row.item.links.self)">
                            <translate key="lang_btn_edit">Edit</translate>
                        </b-button>
                        <b-button v-if="row.item.id !== 1" size="sm" variant="danger"
                                  @click.prevent="doDelete(row.item.links.self)">
                            <translate key="lang_btn_delete">Delete</translate>
                        </b-button>
                    </b-button-group>
                </template>
            </data-table>
        </b-card>

        <edit-modal ref="editModal" :create-url="listUrl" :station-permissions="stationPermissions" :stations="stations"
                    :global-permissions="globalPermissions" @relist="relist"></edit-modal>
    </div>
</template>

<script>
import DataTable from '~/components/Common/DataTable';
import EditModal from './Permissions/EditModal';
import Icon from '~/components/Common/Icon';
import InfoCard from '~/components/Common/InfoCard';
import _ from 'lodash';

export default {
    name: 'AdminPermissions',
    components: {InfoCard, Icon, EditModal, DataTable},
    props: {
        listUrl: String,
        stations: Array,
        globalPermissions: Array,
        stationPermissions: Array
    },
    data() {
        return {
            fields: [
                {key: 'name', isRowHeader: true, label: this.$gettext('Role Name'), sortable: true},
                {key: 'permissions', label: this.$gettext('Permissions'), sortable: false},
                {key: 'actions', label: this.$gettext('Actions'), sortable: false, class: 'shrink'}
            ]
        };
    },
    methods: {
        getGlobalPermissionNames(permissions) {
            return _.filter(_.map(permissions, (permission) => {
                return _.get(this.globalPermissions, permission, null);
            }));
        },
        getStationPermissionNames(permissions) {
            return _.filter(_.map(permissions, (permission) => {
                return _.get(this.stationPermissions, permission, null);
            }));
        },
        getStationName(stationId) {
            return _.get(this.stations, stationId, null);
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
        doDelete(url) {
            this.$confirmDelete({
                title: this.$gettext('Delete Role?'),
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
