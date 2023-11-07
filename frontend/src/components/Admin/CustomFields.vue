<template>
    <card-page :title="$gettext('Custom Fields')">
        <template #info>
            <p class="card-text">
                {{
                    $gettext('Create custom fields to store extra metadata about each media file uploaded to your station libraries.')
                }}
            </p>
        </template>
        <template #actions>
            <add-button
                :text="$gettext('Add Custom Field')"
                @click="doCreate"
            />
        </template>

        <data-table
            id="custom_fields"
            ref="$dataTable"
            :fields="fields"
            :show-toolbar="false"
            :api-url="listUrl"
        >
            <template #cell(name)="row">
                {{ row.item.name }} <code>{{ row.item.short_name }}</code>
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
        :auto-assign-types="autoAssignTypes"
        @relist="relist"
    />
</template>

<script setup lang="ts">
import DataTable, { DataTableField } from '~/components/Common/DataTable.vue';
import EditModal from './CustomFields/EditModal.vue';
import {get} from 'lodash';
import {useTranslate} from "~/vendor/gettext";
import {ref} from "vue";
import useHasDatatable, {DataTableTemplateRef} from "~/functions/useHasDatatable";
import useHasEditModal, {EditModalTemplateRef} from "~/functions/useHasEditModal";
import useConfirmAndDelete from "~/functions/useConfirmAndDelete";
import CardPage from "~/components/Common/CardPage.vue";
import {getApiUrl} from "~/router";
import AddButton from "~/components/Common/AddButton.vue";

const props = defineProps({
    autoAssignTypes: {
        type: Object,
        required: true
    }
});

const listUrl = getApiUrl('/admin/custom_fields');

const {$gettext} = useTranslate();

const fields: DataTableField[] = [
    {
        key: 'name',
        isRowHeader: true,
        label: $gettext('Field Name'),
        sortable: false
    },
    {
        key: 'auto_assign',
        label: $gettext('Auto-Assign Value'),
        sortable: false,
        formatter: (value) => {
            return get(props.autoAssignTypes, value, $gettext('None'));
        }
    },
    {
        key: 'actions',
        label: $gettext('Actions'),
        sortable: false,
        class: 'shrink'
    }
];

const $dataTable = ref<DataTableTemplateRef>(null);
const {relist} = useHasDatatable($dataTable);

const $editModal = ref<EditModalTemplateRef>(null);
const {doCreate, doEdit} = useHasEditModal($editModal);

const {doDelete} = useConfirmAndDelete(
    $gettext('Delete Custom Field?'),
    relist
);
</script>
