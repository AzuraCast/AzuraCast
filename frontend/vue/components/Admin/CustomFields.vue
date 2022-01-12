<template>
    <div>
        <b-card no-body>
            <b-card-header header-bg-variant="primary-dark">
                <h2 class="card-title" key="lang_title" v-translate>Custom Fields</h2>
            </b-card-header>

            <info-card>
                <p class="card-text">
                    <translate key="lang_card_info">Create custom fields to store extra metadata about each media file uploaded to your station libraries.</translate>
                </p>
            </info-card>

            <b-card-body body-class="card-padding-sm">
                <b-button variant="outline-primary" @click.prevent="doCreate">
                    <icon icon="add"></icon>
                    <translate key="lang_add_btn">Add Custom Field</translate>
                </b-button>
            </b-card-body>

            <data-table ref="datatable" id="custom_fields" :fields="fields" :show-toolbar="false" :api-url="listUrl">
                <template #cell(name)="row">
                    {{ row.item.name }} <code>{{ row.item.short_name }}</code>
                </template>
                <template #cell(auto_assign)="row">
                    {{ getAutoAssignName(row.item.auto_assign) }}
                </template>
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
            </data-table>
        </b-card>

        <edit-modal ref="editModal" :create-url="listUrl" :auto-assign-types="autoAssignTypes"
                    @relist="relist"></edit-modal>
    </div>
</template>

<script>
import DataTable from '~/components/Common/DataTable';
import EditModal from './CustomFields/EditModal';
import Icon from '~/components/Common/Icon';
import InfoCard from '~/components/Common/InfoCard';
import _ from 'lodash';

export default {
    name: 'AdminCustomFields',
    components: {InfoCard, Icon, EditModal, DataTable},
    props: {
        listUrl: String,
        autoAssignTypes: Object
    },
    data() {
        return {
            fields: [
                {key: 'name', isRowHeader: true, label: this.$gettext('Field Name'), sortable: false},
                {key: 'auto_assign', label: this.$gettext('Auto-Assign Value'), sortable: false},
                {key: 'actions', label: this.$gettext('Actions'), sortable: false, class: 'shrink'}
            ]
        };
    },
    methods: {
        getAutoAssignName(autoAssign) {
            return _.get(this.autoAssignTypes, autoAssign, this.$gettext('None'));
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
                title: this.$gettext('Delete Custom Field?')
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
