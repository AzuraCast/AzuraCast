<template>
    <card-page :title="$gettext('API Keys')">
        <template #info>
            <p class="card-text">
                {{
                    $gettext('This page lists all API keys assigned to all users across the system.')
                }}
            </p>
        </template>
        <template #actions>
            <b-button
                variant="outline-primary"
                :href="myApiKeysUrl"
                target="_blank"
            >
                <icon icon="vpn_key" />
                {{ $gettext('Manage My API Keys') }}
            </b-button>
        </template>

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
    </card-page>
</template>

<script setup>
import DataTable from "~/components/Common/DataTable.vue";
import {ref} from "vue";
import {useTranslate} from "~/vendor/gettext";
import Icon from "~/components/Common/Icon.vue";
import useConfirmAndDelete from "~/functions/useConfirmAndDelete";
import useHasDatatable from "~/functions/useHasDatatable";
import CardPage from "~/components/Common/CardPage.vue";

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
