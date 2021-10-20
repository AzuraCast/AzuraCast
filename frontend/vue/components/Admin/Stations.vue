<template>
    <div>
        <b-card no-body>
            <b-card-header header-bg-variant="primary-dark">
                <h2 class="card-title" key="lang_title" v-translate>Stations</h2>
            </b-card-header>

            <b-card-body body-class="card-padding-sm">
                <b-button variant="outline-primary" @click.prevent="doCreate">
                    <icon icon="add"></icon>
                    <translate key="lang_add_btn">Add Station</translate>
                </b-button>
            </b-card-body>

            <data-table ref="datatable" id="permissions" :fields="fields" :show-toolbar="false" :api-url="listUrl">
                <template #cell(actions)="row">
                    <b-button-group size="sm">
                        <!--
                        TODO
                        <a class="btn btn-sm btn-primary" href="<?=$router->named('stations:index:index',
                            ['station_id' => $record['id']])?>" target="_blank"><?=__('Manage')?></a>
                        <a class="btn btn-sm btn-dark" href="<?=$router->named('admin:stations:clone',
                            ['id' => $record['id']])?>"><?=__('Clone')?></a>
                        -->
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

        <admin-stations-edit-modal ref="editModal" create-url="listUrl" v-bind="$props"
                                   @relist="relist"></admin-stations-edit-modal>
    </div>
</template>

<script>
import DataTable from '~/components/Common/DataTable';
import Icon from '~/components/Common/Icon';
import InfoCard from '~/components/Common/InfoCard';
import '~/vendor/sweetalert.js';
import {StationFormProps} from "./Stations/StationForm";
import AdminStationsEditModal from "./Stations/EditModal";

export default {
    name: 'AdminPermissions',
    components: {AdminStationsEditModal, InfoCard, Icon, DataTable},
    mixins: [
        StationFormProps
    ],
    props: {
        listUrl: String,
    },
    data() {
        return {
            fields: [
                {key: 'name', isRowHeader: true, label: this.$gettext('Name'), sortable: false},
                {key: 'actions', label: this.$gettext('Actions'), sortable: false, class: 'shrink'}
            ]
        };
    },
    methods: {
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
                title: this.$gettext('Delete Station?'),
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
