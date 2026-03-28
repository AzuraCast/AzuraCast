<template>
    <card-page :title="$gettext('Remote Relays')">
        <template #info>
            <p class="card-text">
                {{
                    $gettext('Remote relays let you work with broadcasting software outside this server. Any relay you include here will be included in your station\'s statistics. You can also broadcast from this server to remote relays.')
                }}
            </p>
        </template>
        <template #actions>
            <add-button
                :text="$gettext('Add Remote Relay')"
                @click="doCreate"
            />
        </template>

        <data-table
            id="station_remotes"
            paginated
            :fields="fields"
            :provider="listItemProvider"
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
                <div
                    v-if="row.item.is_editable"
                    class="btn-group btn-group-sm"
                >
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

    <remote-edit-modal
        ref="$editModal"
        :create-url="listUrl"
        @relist="() => relist()"
        @needs-restart="() => mayNeedRestart()"
    />
</template>

<script setup lang="ts">
import DataTable, {DataTableField} from "~/components/Common/DataTable.vue";
import RemoteEditModal from "~/components/Stations/Remotes/EditModal.vue";
import {useMayNeedRestart} from "~/functions/useMayNeedRestart";
import {useTranslate} from "~/vendor/gettext";
import {useTemplateRef} from "vue";
import showFormatAndBitrate from "~/functions/showFormatAndBitrate";
import useHasEditModal from "~/functions/useHasEditModal";
import useConfirmAndDelete from "~/functions/useConfirmAndDelete";
import CardPage from "~/components/Common/CardPage.vue";
import AddButton from "~/components/Common/AddButton.vue";
import {useApiItemProvider} from "~/functions/dataTable/useApiItemProvider.ts";
import {QueryKeys, queryKeyWithStation} from "~/entities/Queries.ts";
import {HasLinks, StationRemote} from "~/entities/ApiInterfaces.ts";
import {useApiRouter} from "~/functions/useApiRouter.ts";

const {getStationApiUrl} = useApiRouter();
const listUrl = getStationApiUrl('/remotes');

const {$gettext} = useTranslate();

type Row = Required<StationRemote & HasLinks>;

const fields: DataTableField<Row>[] = [
    {key: 'display_name', isRowHeader: true, label: $gettext('Name'), sortable: true},
    {key: 'enable_autodj', label: $gettext('AutoDJ'), sortable: true},
    {key: 'actions', label: $gettext('Actions'), sortable: false, class: 'shrink'}
];

const listItemProvider = useApiItemProvider<Row>(
    listUrl,
    queryKeyWithStation([
        QueryKeys.StationRemotes
    ])
);

const relist = () => {
    void listItemProvider.refresh();
};

const $editModal = useTemplateRef('$editModal');
const {doCreate, doEdit} = useHasEditModal($editModal);

const {mayNeedRestart} = useMayNeedRestart();

const {doDelete} = useConfirmAndDelete(
    $gettext('Delete Remote Relay?'),
    () => {
        mayNeedRestart();
        relist();
    }
);
</script>
