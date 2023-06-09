<template>
    <section
        class="card"
        role="region"
        aria-labelledby="hdr_relays"
    >
        <b-card-header header-bg-variant="primary-dark">
            <h2
                id="hdr_relays"
                class="card-title"
            >
                {{ $gettext('Connected Relays') }}
            </h2>
        </b-card-header>

        <info-card>
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
        </info-card>

        <data-table
            id="relays"
            ref="$datatable"
            paginated
            :fields="fields"
            :api-url="listUrl"
            handle-client-side
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
    </section>
</template>

<script setup>
import DataTable from '~/components/Common/DataTable';
import {useTranslate} from "~/vendor/gettext";
import {ref} from "vue";
import useHasDatatable from "~/functions/useHasDatatable";
import InfoCard from "~/components/Common/InfoCard.vue";
import {DateTime} from "luxon";
import {useAzuraCast} from "~/vendor/azuracast";

const props = defineProps({
    listUrl: {
        type: String,
        required: true
    }
});

const {$gettext} = useTranslate();

const {timeConfig} = useAzuraCast();

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
