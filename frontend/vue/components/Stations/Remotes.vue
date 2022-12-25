<template>
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

    <remote-edit-modal ref="editmodal" :create-url="listUrl"
                       @relist="relist" @needs-restart="mayNeedRestart"></remote-edit-modal>
</template>

<script setup>
import DataTable from '~/components/Common/DataTable';
import Icon from '~/components/Common/Icon';
import InfoCard from '~/components/Common/InfoCard';
import RemoteEditModal from "./Remotes/EditModal";
import '~/vendor/sweetalert';
import {mayNeedRestartProps, useMayNeedRestart} from "~/components/Stations/Common/useMayNeedRestart";
import {useTranslate} from "~/vendor/gettext";
import {ref} from "vue";
import {useSweetAlert} from "~/vendor/sweetalert";
import {useNotify} from "~/vendor/bootstrapVue";
import {useAxios} from "~/vendor/axios";

const props = defineProps({
    ...mayNeedRestartProps,
    listUrl: String,
});

const {$gettext} = useTranslate();

const fields = [
    {key: 'display_name', isRowHeader: true, label: $gettext('Name'), sortable: true},
    {key: 'enable_autodj', label: $gettext('AutoDJ'), sortable: true},
    {key: 'actions', label: $gettext('Actions'), sortable: false, class: 'shrink'}
];

const upper = (data) => {
    if (!data) {
        return '';
    }

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
        title: $gettext('Delete Remote Relay?'),
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
