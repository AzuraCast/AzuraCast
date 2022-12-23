<template>
    <section class="card" role="region">
        <b-card-header header-bg-variant="primary-dark">
            <h2 class="card-title">{{ $gettext('API Keys') }}</h2>
        </b-card-header>

        <data-table ref="datatable" id="api_keys" :fields="fields" :api-url="apiUrl">
            <template #cell(owner)="row">
                {{ row.item.user.email }}
            </template>
            <template #cell(actions)="row">
                <b-button-group size="sm">
                    <b-button size="sm" variant="danger" @click.prevent="doDelete(row.item.links.self)">
                        {{ $gettext('Delete') }}
                    </b-button>
                </b-button-group>
            </template>
        </data-table>
    </section>
</template>

<script setup>
import DataTable from "~/components/Common/DataTable";
import {ref} from "vue";
import {useSweetAlert} from "~/vendor/sweetalert";
import {useNotify} from "~/vendor/bootstrapVue";
import {useAxios} from "~/vendor/axios";
import {useTranslate} from "~/vendor/gettext";

const props = defineProps({
    apiUrl: String
});

const fields = ref([
    {
        key: 'comment',
        isRowHeader: true,
        label: this.$gettext('API Key Description/Comments'),
        sortable: false
    },
    {
        key: 'owner',
        label: this.$gettext('Owner'),
        sortable: false
    },
    {key: 'actions', label: this.$gettext('Actions'), sortable: false, class: 'shrink'}
]);

const datatable = ref(); // Datatable

const relist = () => {
    datatable.value.relist();
};

const {confirmDelete} = useSweetAlert();
const {wrapWithLoading, notifySuccess} = useNotify();
const {axios} = useAxios();
const {$gettext} = useTranslate();

const doDelete = (url) => {
    confirmDelete({
        title: this.$gettext('Delete API Key?'),
    }).then((result) => {
        if (result.value) {
            this.$wrapWithLoading(
                this.axios.delete(url)
            ).then((resp) => {
                this.$notifySuccess(resp.data.message);
                this.relist();
            });
        }
    });
}


</script>
