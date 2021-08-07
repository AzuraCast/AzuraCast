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

            <data-table ref="datatable" id="station_mounts" :show-toolbar="false" :fields="fields"
                        :api-url="listUrl">
                <template #cell(name)="row">
                    <h5 class="m-0">
                        <a :href="row.item.links.listen">{{ row.item.display_name }}</a>
                    </h5>
                    <div v-if="row.item.is_default">
                        <span class="badge badge-success" key="lang_default_mount" v-translate>Default Mount</span>
                    </div>
                </template>
                <template #cell(autodj)="row">
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
                    :enable-advanced-features="enableAdvancedFeatures"
                    :station-frontend-type="stationFrontendType" @relist="relist"></edit-modal>
    </div>
</template>

<script>
import DataTable from '../Common/DataTable';
import axios from 'axios';
import EditModal from './Mounts/EditModal';
import Icon from '../Common/Icon';
import InfoCard from '../Common/InfoCard';
import handleAxiosError from '../Function/handleAxiosError';

export default {
    name: 'StationMounts',
    components: { InfoCard, Icon, EditModal, DataTable },
    props: {
        listUrl: String,
        newIntroUrl: String,
        stationFrontendType: String,
        enableAdvancedFeatures: Boolean
    },
    data () {
        return {
            fields: [
                { key: 'name', isRowHeader: true, label: this.$gettext('Name'), sortable: false },
                { key: 'autodj', label: this.$gettext('AutoDJ'), sortable: false },
                { key: 'actions', label: this.$gettext('Actions'), sortable: false, class: 'shrink' }
            ]
        };
    },
    filters: {
        upper (data) {
            let upper = [];
            data.split(' ').forEach((word) => {
                upper.push(word.toUpperCase());
            });
            return upper.join(' ');
        }
    },
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
        doDelete (url) {
            let buttonText = this.$gettext('Delete');
            let buttonConfirmText = this.$gettext('Delete Mount Point?');

            Swal.fire({
                title: buttonConfirmText,
                confirmButtonText: buttonText,
                confirmButtonColor: '#e64942',
                showCancelButton: true,
                focusCancel: true
            }).then((result) => {
                if (result.value) {
                    axios.delete(url).then((resp) => {
                        notify('<b>' + resp.data.message + '</b>', 'success');

                        this.relist();
                    }).catch((err) => {
                        handleAxiosError(err);
                    });
                }
            });
        }
    }
};
</script>
