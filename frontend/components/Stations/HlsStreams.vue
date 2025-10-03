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
            <add-button
                :text="$gettext('Add HLS Stream')"
                @click="doCreate"
            />
        </template>

        <data-table
            id="station_hls_streams"
            :fields="fields"
            :provider="listItemProvider"
            paginated
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
        @relist="() => relist()"
        @needs-restart="() => mayNeedRestart()"
    />
</template>

<script setup lang="ts">
import DataTable from "~/components/Common/DataTable.vue";
import EditModal from "~/components/Stations/HlsStreams/EditModal.vue";
import {useTranslate} from "~/vendor/gettext";
import {useTemplateRef} from "vue";
import {useMayNeedRestart} from "~/functions/useMayNeedRestart";
import useHasEditModal from "~/functions/useHasEditModal";
import useConfirmAndDelete from "~/functions/useConfirmAndDelete";
import CardPage from "~/components/Common/CardPage.vue";
import AddButton from "~/components/Common/AddButton.vue";
import {useApiItemProvider} from "~/functions/dataTable/useApiItemProvider.ts";
import {QueryKeys, queryKeyWithStation} from "~/entities/Queries.ts";
import {useApiRouter} from "~/functions/useApiRouter.ts";

const {getStationApiUrl} = useApiRouter();
const listUrl = getStationApiUrl('/hls_streams');

const {$gettext} = useTranslate();

const fields = [
    {key: 'name', isRowHeader: true, label: $gettext('Name'), sortable: true},
    {key: 'format', label: $gettext('Format'), sortable: true},
    {key: 'bitrate', label: $gettext('Bitrate'), sortable: true},
    {key: 'actions', label: $gettext('Actions'), sortable: false, class: 'shrink'}
];

const listItemProvider = useApiItemProvider(
    listUrl,
    queryKeyWithStation([QueryKeys.StationHlsStreams])
);

const relist = () => {
    void listItemProvider.refresh();
}

const upper = (data: string) => {
    const upper: string[] = [];
    data.split(' ').forEach((word) => {
        upper.push(word.toUpperCase());
    });
    return upper.join(' ');
};

const $editModal = useTemplateRef('$editModal');
const {doCreate, doEdit} = useHasEditModal($editModal);

const {mayNeedRestart} = useMayNeedRestart();

const {doDelete} = useConfirmAndDelete(
    $gettext('Delete HLS Stream?'),
    () => {
        mayNeedRestart();
        relist();
    }
);
</script>
