<template>
    <div>
        <b-card no-body>
            <b-card-header header-bg-variant="primary-dark">
                <h2 class="card-title" key="lang_title" v-translate>Users</h2>
            </b-card-header>

            <b-card-body body-class="card-padding-sm">
                <b-button variant="outline-primary" @click.prevent="doCreate">
                    <icon icon="add"></icon>
                    <translate key="lang_add_btn">Add User</translate>
                </b-button>
            </b-card-body>

            <data-table ref="datatable" id="users" paginated :fields="fields" :api-url="listUrl">
                <template #cell(name)="row">
                    <h5 class="mb-0" v-if="row.item.name !== ''">{{ row.item.name }}</h5>
                    <a :href="'mailto:'+row.item.email">{{ row.item.email }}</a>
                    <span v-if="row.item.is_me" class="badge badge-primary">
                        <translate key="lang_is_me">You</translate>
                    </span>
                </template>
                <template #cell(roles)="row">
                    <div v-for="role in row.item.roles" :key="role.id">
                        {{ role.name }}
                    </div>
                </template>
                <template #cell(actions)="row">
                    <b-button-group size="sm" v-if="!row.item.is_me">
                        <b-button size="sm" variant="secondary" :href="row.item.links.masquerade" target="_blank">
                            <translate key="lang_btn_masquerade">Log In</translate>
                        </b-button>
                        <b-button size="sm" variant="primary" @click.prevent="doEdit(row.item.links.self)">
                            <translate key="lang_btn_edit">Edit</translate>
                        </b-button>
                        <b-button size="sm" variant="danger" @click.prevent="doDelete(row.item.links.self)">
                            <translate key="lang_btn_delete">Delete</translate>
                        </b-button>
                    </b-button-group>
                </template>
            </data-table>
        </b-card>

        <edit-modal ref="editModal" :create-url="listUrl" :roles="roles" @relist="relist"></edit-modal>
    </div>
</template>

<script>
import DataTable from '~/components/Common/DataTable';
import EditModal from './Users/EditModal';
import Icon from '~/components/Common/Icon';
import InfoCard from '~/components/Common/InfoCard';

export default {
    name: 'AdminPermissions',
    components: {InfoCard, Icon, EditModal, DataTable},
    props: {
        listUrl: String,
        roles: Object
    },
    data() {
        return {
            fields: [
                {key: 'name', isRowHeader: true, label: this.$gettext('User Name'), sortable: true},
                {key: 'roles', label: this.$gettext('Roles'), sortable: false},
                {key: 'actions', label: this.$gettext('Actions'), sortable: false, class: 'shrink'}
            ]
        };
    },
    methods: {
        isMe(userId) {
            return this.currentUserId === userId;
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
                title: this.$gettext('Delete User?'),
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
