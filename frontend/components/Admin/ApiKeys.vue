<template>
    <card-page :title="$gettext('API Keys')">
        <template #info>
            <p class="card-text">
                {{
                    $gettext('This page lists all API keys assigned to all users across the system. To manage your own API keys, visit your account profile.')
                }}
            </p>
        </template>

        <data-table
            id="api_keys"
            ref="$dataTable"
            :fields="fields"
            :api-url="apiUrl"
        >
            <template #cell(actions)="row">
                <div class="btn-group btn-group-sm">
                    <button
                        type="button"
                        class="btn btn-sm btn-danger"
                        @click="doDelete(row.item.links.self)"
                    >
                        {{ $gettext('Delete') }}
                    </button>
                </div>
            </template>
        </data-table>
    </card-page>
</template>

<script setup lang="ts">
import DataTable, {DataTableField} from "~/components/Common/DataTable.vue";
import {useTemplateRef} from "vue";
import {useTranslate} from "~/vendor/gettext";
import useConfirmAndDelete from "~/functions/useConfirmAndDelete";
import useHasDatatable from "~/functions/useHasDatatable";
import CardPage from "~/components/Common/CardPage.vue";
import {getApiUrl} from "~/router";

const apiUrl = getApiUrl('/admin/api-keys');

const {$gettext} = useTranslate();

const fields: DataTableField[] = [
    {
        key: 'comment',
        isRowHeader: true,
        label: $gettext('API Key Description/Comments'),
        sortable: false
    },
    {
        key: 'user.email',
        label: $gettext('Owner'),
        sortable: false
    },
    {
        key: 'actions',
        label: $gettext('Actions'),
        sortable: false,
        class: 'shrink'
    }
];

const $dataTable = useTemplateRef('$dataTable');
const {relist} = useHasDatatable($dataTable);

const {doDelete} = useConfirmAndDelete(
    $gettext('Delete API Key?'),
    relist
);
</script>
