<template>
    <b-card no-body>
        <b-card-header header-bg-variant="primary-dark">
            <h2 class="card-title">
                {{ $gettext('Mount Points') }}
            </h2>
        </b-card-header>

        <info-card>
            <p class="card-text">
                {{
                    $gettext('Mount points are how listeners connect and listen to your station. Each mount point can be a different audio format or quality. Using mount points, you can set up a high-quality stream for broadband listeners and a mobile stream for phone users.')
                }}
            </p>
        </info-card>

        <b-card-body body-class="card-padding-sm">
            <b-button
                variant="outline-primary"
                @click.prevent="doCreate"
            >
                <icon icon="add" />
                {{ $gettext('Add Mount Point') }}
            </b-button>
        </b-card-body>

        <data-table
            id="station_mounts"
            ref="$dataTable"
            :fields="fields"
            paginated
            :api-url="listUrl"
        >
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
                    {{ $gettext('Enabled') }} -
                    {{ showFormatAndBitrate(row.item.autodj_format, row.item.autodj_bitrate) }}
                </template>
                <template v-else>
                    {{ $gettext('Disabled') }}
                </template>
            </template>
            <template #cell(actions)="row">
                <b-button-group size="sm">
                    <b-button
                        size="sm"
                        variant="primary"
                        @click.prevent="doEdit(row.item.links.self)"
                    >
                        {{ $gettext('Edit') }}
                    </b-button>
                    <b-button
                        size="sm"
                        variant="danger"
                        @click.prevent="doDelete(row.item.links.self)"
                    >
                        {{ $gettext('Delete') }}
                    </b-button>
                </b-button-group>
            </template>
        </data-table>
    </b-card>

    <edit-modal
        ref="$editModal"
        :create-url="listUrl"
        :new-intro-url="newIntroUrl"
        :show-advanced="showAdvanced"
        :station-frontend-type="stationFrontendType"
        @relist="relist"
        @needs-restart="mayNeedRestart"
    />
</template>

<script setup>
import DataTable from '~/components/Common/DataTable';
import EditModal from './Mounts/EditModal';
import Icon from '~/components/Common/Icon';
import InfoCard from '~/components/Common/InfoCard';
import {mayNeedRestartProps, useMayNeedRestart} from "~/functions/useMayNeedRestart";
import {useTranslate} from "~/vendor/gettext";
import {ref} from "vue";
import showFormatAndBitrate from "~/functions/showFormatAndBitrate";
import useHasDatatable from "~/functions/useHasDatatable";
import useHasEditModal from "~/functions/useHasEditModal";
import useConfirmAndDelete from "~/functions/useConfirmAndDelete";

const props = defineProps({
    ...mayNeedRestartProps,
    listUrl: {
        type: String,
        required: true
    },
    newIntroUrl: {
        type: String,
        required: true
    },
    stationFrontendType: {
        type: String,
        required: true
    },
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

const $dataTable = ref(); // DataTable
const {relist} = useHasDatatable($dataTable);

const $editModal = ref(); // EditModal
const {doCreate, doEdit} = useHasEditModal($editModal);

const {needsRestart, mayNeedRestart} = useMayNeedRestart(props);

const {doDelete} = useConfirmAndDelete(
    $gettext('Delete Mount Point?'),
    () => {
        needsRestart();
        relist();
    }
);
</script>

