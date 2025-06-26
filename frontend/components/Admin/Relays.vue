<template>
    <card-page :title="$gettext('Connected Relays')">
        <template #info>
            <p class="card-text">
                {{
                    $gettext('Relay is a standalone service that connects to your SoundMesh Radio instance, automatically relays your stations via its own server, then reports the listener details back to your main instance. This page shows all currently connected instances.')
                }}
            </p>
        </template>

        <data-table
            id="relays"
            paginated
            :fields="fields"
            :provider="listItemProvider"
        >
            <template #cell(name)="{ item }">
                <h5>
                    <a
                        :href="item.base_url"
                        target="_blank"
                    >
                        {{ item.name }}
                    </a>
                </h5>
            </template>
            <template #cell(is_visible_on_public_pages)="{ item }">
                <span v-if="item.is_visible_on_public_pages">
                    {{ $gettext('Yes') }}
                </span>
                <span v-else>
                    {{ $gettext('No') }}
                </span>
            </template>
        </data-table>
    </card-page>
</template>

<script setup lang="ts">
import DataTable, {DataTableField} from "~/components/Common/DataTable.vue";
import {useTranslate} from "~/vendor/gettext";
import {useAzuraCast} from "~/vendor/azuracast";
import CardPage from "~/components/Common/CardPage.vue";
import {useLuxon} from "~/vendor/luxon";
import {getApiUrl} from "~/router";
import {Relay} from "~/entities/ApiInterfaces.ts";
import {useApiItemProvider} from "~/functions/dataTable/useApiItemProvider.ts";
import {QueryKeys} from "~/entities/Queries.ts";

const listUrl = getApiUrl('/admin/relays/list');

const {$gettext} = useTranslate();

const {timeConfig} = useAzuraCast();

const {DateTime} = useLuxon();

const dateTimeFormatter = (value: number) => {
    return DateTime.fromSeconds(value).toLocaleString(
        {
            ...DateTime.DATETIME_SHORT, ...timeConfig
        }
    );
}

const fields: DataTableField<Relay>[] = [
    {key: 'name', isRowHeader: true, label: $gettext('Relay'), sortable: true},
    {key: 'is_visible_on_public_pages', label: $gettext('Is Public'), sortable: true},
    {key: 'created_at', label: $gettext('First Connected'), formatter: dateTimeFormatter, sortable: true},
    {key: 'updated_at', label: $gettext('Latest Update'), formatter: dateTimeFormatter, sortable: true}
];

const listItemProvider = useApiItemProvider<Relay>(
    listUrl,
    [QueryKeys.AdminRelays]
);
</script>
