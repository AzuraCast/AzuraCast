<template>
    <section
        class="card"
        role="region"
        aria-labelledby="hdr_api_keys"
    >
        <b-card-header header-bg-variant="primary-dark">
            <h2
                id="hdr_api_keys"
                class="card-title"
            >
                {{ $gettext('API Keys') }}
            </h2>
        </b-card-header>

        <info-card>
            <p class="card-text">
                {{
                    $gettext('This page lists all API keys assigned to all users across the system.')
                }}
            </p>
        </info-card>

        <b-card-body body-class="card-padding-sm">
            <b-button
                variant="outline-primary"
                :href="myApiKeysUrl"
                target="_blank"
            >
                <icon icon="vpn_key" />
                {{ $gettext('Manage My API Keys') }}
            </b-button>
        </b-card-body>

        <data-table
            id="api_keys"
            ref="$datatable"
            :fields="fields"
            :api-url="apiUrl"
        >
            <template #cell(actions)="row">
                <b-button-group size="sm">
                    <b-button
                        size="sm"
                        variant="danger"
                        @click.prevent="doDelete(row.item.links.self)"
                    >
                        {{ $gettext('Delete') }}
                    </b-button>
                </b-button-group>
            </template>
        </data-table>
    </section>
</template>

<script setup>
import DataTable from "~/components/Common/DataTable.vue";
import {ref} from "vue";
import {useTranslate} from "~/vendor/gettext";
import InfoCard from "~/components/Common/InfoCard.vue";
import Icon from "~/components/Common/Icon.vue";
import useConfirmAndDelete from "~/functions/useConfirmAndDelete";
import useHasDatatable from "~/functions/useHasDatatable";

defineProps({
    apiUrl: {
        type: String,
        required: true
    },
    myApiKeysUrl: {
        type: String,
        required: true
    }
});

const {$gettext} = useTranslate();

const fields = ref([
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
]);

const $datatable = ref();
const {relist} = useHasDatatable($datatable);

const {doDelete} = useConfirmAndDelete(
    $gettext('Delete API Key?'),
    relist
);
</script>
