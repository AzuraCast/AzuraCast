<template>
    <card-page
        header-id="hdr_api_keys"
        :title="$gettext('API Keys')"
    >
        <template #info>
            {{
                $gettext('Use API keys to authenticate with the AzuraCast API using the same permissions as your user account.')
            }}

            <a
                href="/api"
                class="alert-link"
                target="_blank"
            >
                {{ $gettext('API Documentation') }}
            </a>
        </template>
        <template #actions>
            <button
                type="button"
                class="btn btn-primary"
                @click="createApiKey"
            >
                <icon :icon="IconAdd" />
                <span>
                    {{ $gettext('Add API Key') }}
                </span>
            </button>
        </template>

        <data-table
            id="account_api_keys"
            ref="$dataTable"
            :show-toolbar="false"
            :fields="apiKeyFields"
            :api-url="apiKeysApiUrl"
        >
            <template #cell(actions)="row">
                <div class="btn-group btn-group-sm">
                    <button
                        type="button"
                        class="btn btn-danger"
                        @click="deleteApiKey(row.item.links.self)"
                    >
                        {{ $gettext('Delete') }}
                    </button>
                </div>
            </template>
        </data-table>
    </card-page>

    <account-api-key-modal
        ref="$apiKeyModal"
        :create-url="apiKeysApiUrl"
        @relist="relist"
    />
</template>

<script setup lang="ts">

import {IconAdd} from "~/components/Common/icons.ts";
import DataTable, {DataTableField} from "~/components/Common/DataTable.vue";
import CardPage from "~/components/Common/CardPage.vue";
import Icon from "~/components/Common/Icon.vue";
import AccountApiKeyModal from "~/components/Account/ApiKeyModal.vue";
import {ref} from "vue";
import useConfirmAndDelete from "~/functions/useConfirmAndDelete.ts";
import {useTranslate} from "~/vendor/gettext.ts";
import useHasDatatable, {DataTableTemplateRef} from "~/functions/useHasDatatable.ts";
import {getApiUrl} from "~/router.ts";

const apiKeysApiUrl = getApiUrl('/frontend/account/api-keys');

const {$gettext} = useTranslate();

const apiKeyFields: DataTableField[] = [
    {
        key: 'comment',
        isRowHeader: true,
        label: $gettext('API Key Description/Comments'),
        sortable: false
    },
    {
        key: 'actions',
        label: $gettext('Actions'),
        sortable: false,
        class: 'shrink'
    }
];

const $apiKeyModal = ref<InstanceType<typeof AccountApiKeyModal> | null>(null);

const createApiKey = () => {
    $apiKeyModal.value?.create();
};

const $dataTable = ref<DataTableTemplateRef>(null);
const {relist} = useHasDatatable($dataTable);

const {doDelete: deleteApiKey} = useConfirmAndDelete(
    $gettext('Delete API Key?'),
    relist
);
</script>
