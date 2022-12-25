<template>
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

    <edit-modal ref="editmodal" :create-url="listUrl" @relist="relist" @needs-restart="mayNeedRestart"></edit-modal>
</template>

<script setup>
import DataTable from '~/components/Common/DataTable';
import EditModal from './HlsStreams/EditModal';
import Icon from '~/components/Common/Icon';
import InfoCard from '~/components/Common/InfoCard';
import {useTranslate} from "~/vendor/gettext";
import {ref} from "vue";
import {mayNeedRestartProps, useMayNeedRestart} from "~/components/Stations/Common/useMayNeedRestart";
import {useSweetAlert} from "~/vendor/sweetalert";
import {useNotify} from "~/vendor/bootstrapVue";
import {useAxios} from "~/vendor/axios";

const props = defineProps({
    ...mayNeedRestartProps,
    listUrl: String
});

const {$gettext} = useTranslate();

const fields = [
    {key: 'name', isRowHeader: true, label: $gettext('Name'), sortable: true},
    {key: 'format', label: $gettext('Format'), sortable: true},
    {key: 'bitrate', label: $gettext('Bitrate'), sortable: true},
    {key: 'actions', label: $gettext('Actions'), sortable: false, class: 'shrink'}
];

const upper = (data) => {
    let upper = [];
    data.split(' ').forEach((word) => {
        upper.push(word.toUpperCase());
    });
    return upper.join(' ');
};

const datatable = ref(); // DataTable

const relist = () => {
    datatable.value?.refresh();
};

const editmodal = ref(); // EditModal

const doCreate = () => {
    editmodal.value?.create();
};

const doEdit = (url) => {
    editmodal.value?.edit(url);
};

const {mayNeedRestart, needsRestart} = useMayNeedRestart(props.restartStatusUrl);
const {confirmDelete} = useSweetAlert();
const {wrapWithLoading, notifySuccess} = useNotify();
const {axios} = useAxios();

const doDelete = (url) => {
    confirmDelete({
        title: $gettext('Delete HLS Stream?'),
    }).then((result) => {
        if (result.value) {
            wrapWithLoading(
                axios.delete(url)
            ).then((resp) => {
                notifySuccess(resp.data.message);
                needsRestart();
                relist();
            });
        }
    });
};
</script>
