<template>
    <div>
        <b-card no-body>
            <b-card-header header-bg-variant="primary-dark">
                <h2 class="card-title">{{ $gettext('Remote Relays') }}</h2>
            </b-card-header>

            <info-card>
                <p class="card-text">
                    {{
                        $gettext('Remote relays let you work with broadcasting software outside this server. Any relay you include here will be included in your station\'s statistics. You can also broadcast from this server to remote relays.')
                    }}
                </p>
            </info-card>

            <b-card-body body-class="card-padding-sm">
                <b-button variant="outline-primary" @click.prevent="doCreate">
                    <icon icon="add"></icon>
                    {{ $gettext('Add Remote Relay') }}
                </b-button>
            </b-card-body>

            <data-table ref="datatable" id="station_remotes" paginated :fields="fields" :api-url="listUrl">
                <template #cell(display_name)="row">
                    <h5 class="m-0">
                        <a :href="row.item.url" target="_blank">{{ row.item.display_name }}</a>
                    </h5>
                </template>
                <template #cell(enable_autodj)="row">
                    <template v-if="row.item.enable_autodj">
                        {{ $gettext('Enabled') }} - {{ row.item.autodj_bitrate }}kbps {{
                            upper(row.item.autodj_format)
                        }}
                    </template>
                    <template v-else>
                        {{ $gettext('Disabled') }}
                    </template>
                </template>
                <template #cell(actions)="row">
                    <b-button-group size="sm" v-if="row.item.is_editable">
                        <b-button size="sm" variant="primary" @click.prevent="doEdit(row.item.links.self)">
                            {{ $gettext('Edit') }}
                        </b-button>
                        <b-button size="sm" variant="danger" @click.prevent="doDelete(row.item.links.self)">
                            {{ $gettext('Delete') }}
                        </b-button>
                    </b-button-group>
                </template>
            </data-table>
        </b-card>

        <remote-edit-modal ref="editModal" :create-url="listUrl"
                           @relist="relist" @needs-restart="mayNeedRestart"></remote-edit-modal>
    </div>
</template>

<script>
import DataTable from '~/components/Common/DataTable';
import EditModal from './Mounts/EditModal';
import Icon from '~/components/Common/Icon';
import InfoCard from '~/components/Common/InfoCard';
import RemoteEditModal from "./Remotes/EditModal";
import StationMayNeedRestart from '~/components/Stations/Common/MayNeedRestart.vue';
import '~/vendor/sweetalert.js';

export default {
    name: 'StationMounts',
    components: {RemoteEditModal, InfoCard, Icon, EditModal, DataTable},
    mixins: [StationMayNeedRestart],
    props: {
        listUrl: String,
    },
    data() {
        return {
            fields: [
                {key: 'display_name', isRowHeader: true, label: this.$gettext('Name'), sortable: true},
                {key: 'enable_autodj', label: this.$gettext('AutoDJ'), sortable: true},
                {key: 'actions', label: this.$gettext('Actions'), sortable: false, class: 'shrink'}
            ]
        };
    },
    methods: {
        upper(data) {
            if (!data) {
                return '';
            }

            let upper = [];
            data.split(' ').forEach((word) => {
                upper.push(word.toUpperCase());
            });
            return upper.join(' ');
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
                title: this.$gettext('Delete Remote Relay?'),
            }).then((result) => {
                if (result.value) {
                    this.$wrapWithLoading(
                        this.axios.delete(url)
                    ).then((resp) => {
                        this.$notifySuccess(resp.data.message);
                        this.needsRestart();
                        this.relist();
                    });
                }
            });
        },
    }
};
</script>
