<template>
    <div>
        <b-card no-body>
            <b-card-header header-bg-variant="primary-dark">
                <h2 class="card-title">{{ $gettext('HLS Streams') }}</h2>
            </b-card-header>

            <info-card>
                <p class="card-text">
                    {{
                        $gettext('HTTP Live Streaming (HLS) is a new adaptive-bitrate streaming technology. From this page, you can configure the individual bitrates and formats that are included in the combined HLS stream.')
                    }}
                </p>
            </info-card>

            <b-card-body body-class="card-padding-sm">
                <b-button variant="outline-primary" @click.prevent="doCreate">
                    <icon icon="add"></icon>
                    {{ $gettext('Add HLS Stream') }}
                </b-button>
            </b-card-body>

            <data-table ref="datatable" id="station_hls_streams" :fields="fields" paginated
                        :api-url="listUrl">
                <template #cell(name)="row">
                    <h5 class="m-0">{{ row.item.name }}</h5>
                </template>
                <template #cell(format)="row">
                    {{ upper(row.item.format) }}
                </template>
                <template #cell(bitrate)="row">
                    {{ row.item.bitrate }}kbps
                </template>
                <template #cell(actions)="row">
                    <b-button-group size="sm">
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

        <edit-modal ref="editModal" :create-url="listUrl" @relist="relist" @needs-restart="mayNeedRestart"></edit-modal>
    </div>
</template>

<script>
import DataTable from '~/components/Common/DataTable';
import EditModal from './HlsStreams/EditModal';
import Icon from '~/components/Common/Icon';
import InfoCard from '~/components/Common/InfoCard';
import StationMayNeedRestart from '~/components/Stations/Common/MayNeedRestart.vue';

export default {
    name: 'StationHlsStreams',
    components: {InfoCard, Icon, EditModal, DataTable},
    mixins: [StationMayNeedRestart],
    props: {
        listUrl: String
    },
    data() {
        return {
            fields: [
                {key: 'name', isRowHeader: true, label: this.$gettext('Name'), sortable: true},
                {key: 'format', label: this.$gettext('Format'), sortable: true},
                {key: 'bitrate', label: this.$gettext('Bitrate'), sortable: true},
                {key: 'actions', label: this.$gettext('Actions'), sortable: false, class: 'shrink'}
            ]
        };
    },
    methods: {
        upper(data) {
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
                title: this.$gettext('Delete HLS Stream?'),
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
