<template>
    <section
        class="card"
        role="region"
    >
        <b-card-header header-bg-variant="primary-dark">
            <h2 class="card-title">
                {{ $gettext('API Keys') }}
            </h2>
        </b-card-header>

        <data-table
            id="api_keys"
            ref="$dataTable"
            :fields="fields"
            :api-url="apiUrl"
        >
            <template #cell(owner)="row">
                {{ row.item.user.email }}
            </template>
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
import {useSweetAlert} from "~/vendor/sweetalert";
import {useNotify} from "~/vendor/bootstrapVue";
import {useAxios} from "~/vendor/axios";
import {useTranslate} from "~/vendor/gettext";

defineProps({
    apiUrl: {
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
        key: 'owner',
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

const $dataTable = ref();

const relist = () => {
    $dataTable.value.relist();
};

const {confirmDelete} = useSweetAlert();
const {wrapWithLoading, notifySuccess} = useNotify();
const {axios} = useAxios();

const doDelete = (url) => {
    confirmDelete({
        title: $gettext('Delete API Key?'),
    }).then((result) => {
        if (result.value) {
            wrapWithLoading(
                axios.delete(url)
            ).then((resp) => {
                notifySuccess(resp.data.message);
                relist();
            });
        }
    });
}
</script>
