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
            :fields="fields"
            :provider="itemProvider"
        >
            <template #cell(actions)="{ item }">
                <div class="btn-group btn-group-sm">
                    <button
                        type="button"
                        class="btn btn-sm btn-danger"
                        @click="doDelete(item.links.self)"
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
import {useTranslate} from "~/vendor/gettext";
import useConfirmAndDelete from "~/functions/useConfirmAndDelete";
import CardPage from "~/components/Common/CardPage.vue";
import {ApiKey, HasLinks} from "~/entities/ApiInterfaces.ts";
import {useApiItemProvider} from "~/functions/dataTable/useApiItemProvider.ts";
import {QueryKeys} from "~/entities/Queries.ts";
import {useApiRouter} from "~/functions/useApiRouter.ts";

const {getApiUrl} = useApiRouter();
const apiUrl = getApiUrl('/admin/api-keys');

const {$gettext} = useTranslate();

type Row = Required<ApiKey & HasLinks>

const fields: DataTableField<Row>[] = [
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

const itemProvider = useApiItemProvider<Row>(
    apiUrl,
    [QueryKeys.AccountApiKeys]
);

const {doDelete} = useConfirmAndDelete(
    $gettext('Delete API Key?'),
    () => void itemProvider.refresh()
);
</script>
