<template>
    <b-card no-body>
        <b-card-header header-bg-variant="primary-dark">
            <h2 class="card-title">
                {{ $gettext('Custom Fields') }}
            </h2>
        </b-card-header>

        <info-card>
            <p class="card-text">
                {{
                    $gettext('Create custom fields to store extra metadata about each media file uploaded to your station libraries.')
                }}
            </p>
        </info-card>

        <b-card-body body-class="card-padding-sm">
            <b-button
                variant="outline-primary"
                @click.prevent="doCreate"
            >
                <icon icon="add" />
                {{ $gettext('Add Custom Field') }}
            </b-button>
        </b-card-body>

        <data-table
            id="custom_fields"
            ref="$dataTable"
            :fields="fields"
            :show-toolbar="false"
            :api-url="listUrl"
        >
            <template #cell(name)="row">
                {{ row.item.name }} <code>{{ row.item.short_name }}</code>
            </template>
            <template #cell(actions)="row">
                <b-button-group size="sm">
                    <b-button
                        size="sm"
                        variant="primary"
                        @click.prevent="doEdit(row.item.links.self)"
                    >
                        {{ $gettext('Edit') }}
                    </b-button>
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
    </b-card>

    <edit-modal
        ref="$editModal"
        :create-url="listUrl"
        :auto-assign-types="autoAssignTypes"
        @relist="relist"
    />
</template>

<script setup>
import DataTable from '~/components/Common/DataTable.vue';
import EditModal from './CustomFields/EditModal.vue';
import Icon from '~/components/Common/Icon.vue';
import InfoCard from '~/components/Common/InfoCard.vue';
import {get} from 'lodash';
import {useTranslate} from "~/vendor/gettext";
import {ref} from "vue";
import {useSweetAlert} from "~/vendor/sweetalert";
import {useNotify} from "~/vendor/bootstrapVue";
import {useAxios} from "~/vendor/axios";

const props = defineProps({
    listUrl: {
        type: String,
        required: true
    },
    autoAssignTypes: {
        type: Object,
        required: true
    }
});

const {$gettext} = useTranslate();

const fields = [
    {
        key: 'name',
        isRowHeader: true,
        label: $gettext('Field Name'),
        sortable: false
    },
    {
        key: 'auto_assign',
        label: $gettext('Auto-Assign Value'),
        sortable: false,
        formatter: (value) => {
            return get(props.autoAssignTypes, value, $gettext('None'));
        }
    },
    {
        key: 'actions',
        label: $gettext('Actions'),
        sortable: false,
        class: 'shrink'
    }
];

const $dataTable = ref(); // DataTable

const relist = () => {
    $dataTable.value.refresh();
};

const $editModal = ref(); // EditModal

const doCreate = () => {
    $editModal.value.create();
}

const doEdit = (url) => {
    $editModal.value.edit(url);
}

const {confirmDelete} = useSweetAlert();
const {wrapWithLoading, notifySuccess} = useNotify();
const {axios} = useAxios();

const doDelete = (url) => {
    confirmDelete({
        title: $gettext('Delete Custom Field?')
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
};
</script>
