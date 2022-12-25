<template>
    <b-card no-body>
        <b-card-header header-bg-variant="primary-dark">
            <h2 class="card-title">{{ $gettext('Mount Points') }}</h2>
        </b-card-header>

        <info-card>
            <p class="card-text">
                {{
                    $gettext('Mount points are how listeners connect and listen to your station. Each mount point can be a different audio format or quality. Using mount points, you can set up a high-quality stream for broadband listeners and a mobile stream for phone users.')
                }}
            </p>
        </info-card>

        <b-card-body body-class="card-padding-sm">
            <b-button variant="outline-primary" @click.prevent="doCreate">
                <icon icon="add"></icon>
                {{ $gettext('Add Mount Point') }}
            </b-button>
        </b-card-body>

        <data-table ref="datatable" id="station_mounts" :fields="fields" paginated
                    :api-url="listUrl">
            <template #cell(display_name)="row">
                <h5 class="m-0">
                    <a :href="row.item.links.listen">{{ row.item.display_name }}</a>
                </h5>
                <div v-if="row.item.is_default">
                    <span class="badge badge-success">
                        {{ $gettext('Default Mount') }}
                    </span>
                </div>
            </template>
            <template #cell(enable_autodj)="row">
                <template v-if="row.item.enable_autodj">
                    {{ $gettext('Enabled') }}
                    -
                    {{ row.item.autodj_bitrate }}kbps {{ upper(row.item.autodj_format) }}
                </template>
                <template v-else>
                    {{ $gettext('Disabled') }}
                </template>
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

    <edit-modal ref="editmodal" :create-url="listUrl" :new-intro-url="newIntroUrl"
                :show-advanced="showAdvanced" :station-frontend-type="stationFrontendType"
                @relist="relist" @needs-restart="mayNeedRestart"></edit-modal>
</template>

<script setup>
import DataTable from '~/components/Common/DataTable';
import EditModal from './Mounts/EditModal';
import Icon from '~/components/Common/Icon';
import InfoCard from '~/components/Common/InfoCard';
import {mayNeedRestartProps, useMayNeedRestart} from "~/components/Stations/Common/useMayNeedRestart";
import {useTranslate} from "~/vendor/gettext";
import {ref} from "vue";
import {useSweetAlert} from "~/vendor/sweetalert";
import {useNotify} from "~/vendor/bootstrapVue";
import {useAxios} from "~/vendor/axios";

const props = defineProps({
    ...mayNeedRestartProps,
    listUrl: String,
    newIntroUrl: String,
    stationFrontendType: String,
    showAdvanced: {
        type: Boolean,
        default: true
    },
});

const {$gettext} = useTranslate();

const fields = [
    {key: 'display_name', isRowHeader: true, label: $gettext('Name'), sortable: true},
    {key: 'enable_autodj', label: $gettext('AutoDJ'), sortable: true},
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
    datatable.value.refresh();
};

const editmodal = ref(); // EditModal

const doCreate = () => {
    editmodal.value.create();
};

const doEdit = (url) => {
    editmodal.value.edit(url);
};

const {needsRestart, mayNeedRestart} = useMayNeedRestart(props.restartStatusUrl);

const {confirmDelete} = useSweetAlert();
const {wrapWithLoading, notifySuccess} = useNotify();
const {axios} = useAxios();

const doDelete = (url) => {
    confirmDelete({
        title: $gettext('Delete Mount Point?'),
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
}

</script>

