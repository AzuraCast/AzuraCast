<template>
    <card-page :title="$gettext('Connected AzuraRelays')">
        <template #info>
            <p class="card-text">
                {{
                    $gettext('AzuraRelay is a standalone service that connects to your AzuraCast instance, automatically relays your stations via its own server, then reports the listener details back to your main instance. This page shows all currently connected instances.')
                }}
            </p>

            <a
                class="btn btn-sm btn-light"
                target="_blank"
                href="https://github.com/AzuraCast/AzuraRelay"
            >
                {{ $gettext('About AzuraRelay') }}
            </a>
        </template>

        <data-table
            id="relays"
            ref="$datatable"
            paginated
            :fields="fields"
            :api-url="listUrl"
        >
            <template #cell(name)="row">
                <h5>
                    <a
                        :href="row.item.base_url"
                        target="_blank"
                    >
                        {{ row.item.name }}
                    </a>
                </h5>
            </template>
            <template #cell(is_visible_on_public_pages)="row">
                <span v-if="row.item.is_visible_on_public_pages">
                    {{ $gettext('Yes') }}
                </span>
                <span v-else>
                    {{ $gettext('No') }}
                </span>
            </template>
        </data-table>
    </card-page>
</template>

<script setup>
import DataTable from '~/components/Common/DataTable';
import {useTranslate} from "~/vendor/gettext";
import {ref} from "vue";
import useHasDatatable from "~/functions/useHasDatatable";
import {useAzuraCast} from "~/vendor/azuracast";
import CardPage from "~/components/Common/CardPage.vue";
import {useLuxon} from "~/vendor/luxon";
import {getApiUrl} from "~/router";

const listUrl = getApiUrl('/admin/relays/list');

const {$gettext} = useTranslate();

const {timeConfig} = useAzuraCast();

const {DateTime} = useLuxon();

const dateTimeFormatter = (value) => {
    return DateTime.fromSeconds(value).toLocaleString(
        {
            ...DateTime.DATETIME_SHORT, ...timeConfig
        }
    );
}

const fields = [
    {key: 'name', isRowHeader: true, label: $gettext('Relay'), sortable: true},
    {key: 'is_visible_on_public_pages', label: $gettext('Is Public'), sortable: true},
    {key: 'created_at', label: $gettext('First Connected'), formatter: dateTimeFormatter, sortable: true},
    {key: 'updated_at', label: $gettext('Latest Update'), formatter: dateTimeFormatter, sortable: true}
];

const $datatable = ref(); // Template Ref
useHasDatatable($datatable);
</script>
