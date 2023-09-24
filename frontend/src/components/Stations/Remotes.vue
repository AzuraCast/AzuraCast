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
        @relist="relist"
        @needs-restart="mayNeedRestart"
    />
</template>

<script setup lang="ts">
import DataTable, { DataTableField } from '~/components/Common/DataTable.vue';
import RemoteEditModal from "./Remotes/EditModal.vue";
import '~/vendor/sweetalert';
import {useMayNeedRestart} from "~/functions/useMayNeedRestart";
import {useTranslate} from "~/vendor/gettext";
import {ref} from "vue";
import showFormatAndBitrate from "~/functions/showFormatAndBitrate";
import useHasDatatable, {DataTableTemplateRef} from "~/functions/useHasDatatable";
import useHasEditModal, {EditModalTemplateRef} from "~/functions/useHasEditModal";
import useConfirmAndDelete from "~/functions/useConfirmAndDelete";
import CardPage from "~/components/Common/CardPage.vue";
import {getStationApiUrl} from "~/router";
import AddButton from "~/components/Common/AddButton.vue";

const listUrl = getStationApiUrl('/remotes');

const {$gettext} = useTranslate();

const fields: DataTableField[] = [
    {key: 'display_name', isRowHeader: true, label: $gettext('Name'), sortable: true},
    {key: 'enable_autodj', label: $gettext('AutoDJ'), sortable: true},
    {key: 'actions', label: $gettext('Actions'), sortable: false, class: 'shrink'}
];

const $dataTable = ref<DataTableTemplateRef>(null);
const {relist} = useHasDatatable($dataTable);

const $editModal = ref<EditModalTemplateRef>(null);
const {doCreate, doEdit} = useHasEditModal($editModal);

const {mayNeedRestart, needsRestart} = useMayNeedRestart();

const {doDelete} = useConfirmAndDelete(
    $gettext('Delete Remote Relay?'),
    () => {
        needsRestart();
        relist();
    }
);
</script>
