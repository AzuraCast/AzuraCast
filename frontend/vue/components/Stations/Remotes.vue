<template>
    <b-card no-body>
        <b-card-header header-bg-variant="primary-dark">
            <h2 class="card-title">
                {{ $gettext('Remote Relays') }}
            </h2>
        </b-card-header>

        <info-card>
            <p class="card-text">
                {{
                    $gettext('Remote relays let you work with broadcasting software outside this server. Any relay you include here will be included in your station\'s statistics. You can also broadcast from this server to remote relays.')
                }}
            </p>
        </info-card>

        <b-card-body body-class="card-padding-sm">
            <b-button
                variant="outline-primary"
                @click.prevent="doCreate"
            >
                <icon icon="add" />
                {{ $gettext('Add Remote Relay') }}
            </b-button>
        </b-card-body>

        <data-table
            id="station_remotes"
            ref="$dataTable"
            paginated
            :fields="fields"
            :api-url="listUrl"
        >
            <template #cell(display_name)="row">
                <h5 class="m-0">
                    <a
                        :href="row.item.url"
                        target="_blank"
                    >{{ row.item.display_name }}</a>
                </h5>
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
                <b-button-group
                    v-if="row.item.is_editable"
                    size="sm"
                >
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

    <remote-edit-modal
        ref="$editModal"
        :create-url="listUrl"
        @relist="relist"
        @needs-restart="mayNeedRestart"
    />
</template>

<script setup>
import DataTable from '~/components/Common/DataTable';
import Icon from '~/components/Common/Icon';
import InfoCard from '~/components/Common/InfoCard';
import RemoteEditModal from "./Remotes/EditModal";
import '~/vendor/sweetalert';
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

const {mayNeedRestart, needsRestart} = useMayNeedRestart(props);

const {doDelete} = useConfirmAndDelete(
    $gettext('Delete Remote Relay?'),
    () => {
        needsRestart();
        relist();
    }
);
</script>
