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
                <icon-ic-add/>
                <span>
                    {{ $gettext('Add API Key') }}
                </span>
            </button>
        </template>

        <data-table
            id="account_api_keys"
            :show-toolbar="false"
            :fields="apiKeyFields"
            :provider="apiKeyItemProvider"
        >
            <template #cell(actions)="{ item }">
                <div class="btn-group btn-group-sm">
                    <button
                        type="button"
                        class="btn btn-danger"
                        @click="deleteApiKey(item.links.self)"
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
        @relist="() => refreshApiKeys()"
    />
</template>

<script setup lang="ts">
import IconIcAdd from "~icons/ic/baseline-add";
import DataTable, {DataTableField} from "~/components/Common/DataTable.vue";
import CardPage from "~/components/Common/CardPage.vue";
import AccountApiKeyModal from "~/components/Account/ApiKeyModal.vue";
import {useTemplateRef} from "vue";
import useConfirmAndDelete from "~/functions/useConfirmAndDelete.ts";
import {useTranslate} from "~/vendor/gettext.ts";
import {ApiKey, HasLinks} from "~/entities/ApiInterfaces.ts";
import {useApiItemProvider} from "~/functions/dataTable/useApiItemProvider.ts";
import {QueryKeys} from "~/entities/Queries.ts";
import {useApiRouter} from "~/functions/useApiRouter.ts";

const {getApiUrl} = useApiRouter();
const apiKeysApiUrl = getApiUrl('/frontend/account/api-keys');

const {$gettext} = useTranslate();

type Row = Required<ApiKey & HasLinks>

const apiKeyFields: DataTableField<Row>[] = [
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

const apiKeyItemProvider = useApiItemProvider<Row>(
    apiKeysApiUrl,
    [
        QueryKeys.AdminApiKeys
    ]
);

const refreshApiKeys = () => {
    void apiKeyItemProvider.refresh();
};

const $apiKeyModal = useTemplateRef('$apiKeyModal');

const createApiKey = () => {
    $apiKeyModal.value?.create();
};

const {doDelete: deleteApiKey} = useConfirmAndDelete(
    $gettext('Delete API Key?'),
    () => refreshApiKeys()
);
</script>
