<template>
    <card-page :title="$gettext('HLS Streams')">
        <template #info>
            <p class="card-text">
                {{
                    $gettext('HTTP Live Streaming (HLS) is a new adaptive-bitrate streaming technology. From this page, you can configure the individual bitrates and formats that are included in the combined HLS stream.')
                }}
            </p>
        </template>
        <template #actions>
            <button
                type="button"
                class="btn btn-primary"
                @click.prevent="doCreate"
            >
                <icon icon="add" />
                <span>
                    {{ $gettext('Add HLS Stream') }}
                </span>
            </button>
        </template>

        <data-table
            id="station_hls_streams"
            ref="$dataTable"
            :fields="fields"
            paginated
            :api-url="listUrl"
        >
            <template #cell(name)="row">
                <h5 class="m-0">
                    {{ row.item.name }}
                </h5>
            </template>
            <template #cell(format)="row">
                {{ upper(row.item.format) }}
            </template>
            <template #cell(bitrate)="row">
                {{ row.item.bitrate }}kbps
            </template>
            <template #cell(actions)="row">
                <div class="btn-group btn-group-sm">
                    <button
                        type="button"
                        class="btn btn-primary"
                        @click="doEdit(row.item.links.self)"
                    >
                        {{ $gettext('Edit') }}
                    </button>
                    <button
                        type="button"
                        class="btn btn-danger"
                        @click="doDelete(row.item.links.self)"
                    >
                        {{ $gettext('Delete') }}
                    </button>
                </div>
            </template>
        </data-table>
    </card-page>

    <edit-modal
        ref="$editModal"
        :create-url="listUrl"
        @relist="relist"
        @needs-restart="mayNeedRestart"
    />
</template>

<script setup>
import DataTable from '~/components/Common/DataTable';
import EditModal from './HlsStreams/EditModal';
import Icon from '~/components/Common/Icon';
import {useTranslate} from "~/vendor/gettext";
import {ref} from "vue";
import {useMayNeedRestart} from "~/functions/useMayNeedRestart";
import useHasDatatable from "~/functions/useHasDatatable";
import useHasEditModal from "~/functions/useHasEditModal";
import useConfirmAndDelete from "~/functions/useConfirmAndDelete";
import CardPage from "~/components/Common/CardPage.vue";
import {getStationApiUrl} from "~/router";

const listUrl = getStationApiUrl('/hls_streams');

const {$gettext} = useTranslate();

const fields = [
    {key: 'name', isRowHeader: true, label: $gettext('Name'), sortable: true},
    {key: 'format', label: $gettext('Format'), sortable: true},
    {key: 'bitrate', label: $gettext('Bitrate'), sortable: true},
    {key: 'actions', label: $gettext('Actions'), sortable: false, class: 'shrink'}
];

const upper = (data) => {
    const upper = [];
    data.split(' ').forEach((word) => {
        upper.push(word.toUpperCase());
    });
    return upper.join(' ');
};

const $dataTable = ref(); // DataTable
const {relist} = useHasDatatable($dataTable);

const $editModal = ref(); // EditModal
const {doCreate, doEdit} = useHasEditModal($editModal);

const {mayNeedRestart, needsRestart} = useMayNeedRestart();

const {doDelete} = useConfirmAndDelete(
    $gettext('Delete HLS Stream?'),
    () => {
        needsRestart();
        relist();
    }
);
</script>
