<template>
    <div>
        <b-card no-body>
            <b-card-header header-bg-variant="primary-dark">
                <h2 class="card-title" key="lang_title" v-translate>Mount Points</h2>
            </b-card-header>

            <info-card>
                <p class="card-text">
                    <translate key="lang_card_info">Mount points are how listeners connect and listen to your station. Each mount point can be a different audio format or quality. Using mount points, you can set up a high-quality stream for broadband listeners and a mobile stream for phone users.</translate>
                </p>
            </info-card>

            <b-card-body body-class="card-padding-sm">
                <b-button variant="outline-primary" @click.prevent="doCreate">
                    <icon icon="add"></icon>
                    <translate key="lang_add_btn">Add Mount Point</translate>
                </b-button>
            </b-card-body>

            <data-table ref="datatable" id="station_mounts" :fields="fields" paginated
                        :api-url="listUrl">
                <template #cell(display_name)="row">
                    <h5 class="m-0">
                        <a :href="row.item.links.listen">{{ row.item.display_name }}</a>
                    </h5>
                    <div v-if="row.item.is_default">
                        <span class="badge badge-success" key="lang_default_mount" v-translate>Default Mount</span>
                    </div>
                </template>
                <template #cell(enable_autodj)="row">
                    <template v-if="row.item.enable_autodj">
                        <translate key="lang_autodj_enabled">Enabled</translate>
                        -
                        {{ row.item.autodj_bitrate }}kbps {{ row.item.autodj_format|upper }}
                    </template>
                    <template v-else>
                        <translate key="lang_autodj_disabled">Disabled</translate>
                    </template>
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

        <edit-modal ref="editModal" :create-url="listUrl" :new-intro-url="newIntroUrl"
                    :show-advanced="showAdvanced" :station-frontend-type="stationFrontendType"
                    @relist="relist" @needs-restart="mayNeedRestart"></edit-modal>
    </div>
</template>

<script>
import DataTable from '~/components/Common/DataTable';
import EditModal from './Mounts/EditModal';
import Icon from '~/components/Common/Icon';
import InfoCard from '~/components/Common/InfoCard';
import StationMayNeedRestart from '~/components/Stations/Common/MayNeedRestart.vue';

export default {
    name: 'StationMounts',
    components: {InfoCard, Icon, EditModal, DataTable},
    mixins: [StationMayNeedRestart],
    props: {
        listUrl: String,
        newIntroUrl: String,
        stationFrontendType: String,
        showAdvanced: {
            type: Boolean,
            default: true
        },
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
    filters: {
        upper(data) {
            let upper = [];
            data.split(' ').forEach((word) => {
                upper.push(word.toUpperCase());
            });
            return upper.join(' ');
        }
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
                title: this.$gettext('Delete Mount Point?'),
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
        }
    }
};
</script>
