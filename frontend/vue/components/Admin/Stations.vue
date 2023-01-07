<template>
    <b-card no-body>
        <b-card-header header-bg-variant="primary-dark">
            <h2 class="card-title">
                {{ $gettext('Stations') }}
            </h2>
        </b-card-header>

        <b-card-body body-class="card-padding-sm">
            <b-button
                variant="outline-primary"
                @click.prevent="doCreate"
            >
                <icon icon="add" />
                {{ $gettext('Add Station') }}
            </b-button>
        </b-card-body>

        <data-table
            id="stations"
            ref="$datatable"
            paginated
            :fields="fields"
            :api-url="listUrl"
        >
            <template #cell(name)="row">
                <div class="typography-subheading">
                    {{ row.item.name }}
                </div>
                <code>{{ row.item.short_name }}</code>
            </template>
            <template #cell(actions)="row">
                <b-button-group size="sm">
                    <b-button
                        size="sm"
                        variant="secondary"
                        :href="row.item.links.manage"
                        target="_blank"
                    >
                        {{ $gettext('Manage') }}
                    </b-button>
                    <b-button
                        size="sm"
                        variant="secondary"
                        @click.prevent="doClone(row.item.name, row.item.links.clone)"
                    >
                        {{ $gettext('Clone') }}
                    </b-button>
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

    <admin-stations-edit-modal
        v-bind="pickProps(props, stationFormProps)"
        ref="$editModal"
        :create-url="listUrl"
        @relist="relist"
    />

    <admin-stations-clone-modal
        ref="$cloneModal"
        @relist="relist"
    />
</template>

<script setup>
import DataTable from '~/components/Common/DataTable';
import Icon from '~/components/Common/Icon';
import AdminStationsEditModal from "./Stations/EditModal";
import {get} from "lodash";
import AdminStationsCloneModal from "./Stations/CloneModal";
import stationFormProps from "./Stations/stationFormProps";
import {pickProps} from "~/functions/pickProps";
import {useTranslate} from "~/vendor/gettext";
import {ref} from "vue";
import {useNotify} from "~/vendor/bootstrapVue";
import {useAxios} from "~/vendor/axios";
import {useSweetAlert} from "~/vendor/sweetalert";

const props = defineProps({
    ...stationFormProps,
    listUrl: {
        type: String,
        required: true
    },
    frontendTypes: {
        type: Object,
        required: true
    },
    backendTypes: {
        type: Object,
        required: true
    }
});

const {$gettext} = useTranslate();

const fields = [
    {
        key: 'name',
        isRowHeader: true,
        label: $gettext('Name'),
        sortable: true
    },
    {
        key: 'frontend_type',
        label: $gettext('Broadcasting'),
        sortable: false,
        formatter: (value) => {
            return get(props.frontendTypes, [value, 'name'], '');
        }
    },
    {
        key: 'backend_type',
        label: $gettext('AutoDJ'),
        sortable: false,
        formatter: (value) => {
            return get(props.backendTypes, [value, 'name'], '');
        }
    },
    {
        key: 'actions',
        label: $gettext('Actions'),
        sortable: false,
        class: 'shrink'
    }
];

const $datatable = ref(); // Template Ref

const relist = () => {
    $datatable.value.refresh();
};

const $editModal = ref(); // Template Ref

const doCreate = () => {
    $editModal.value.create();
};

const doEdit = (url) => {
    $editModal.value.edit(url);
};

const $cloneModal = ref(); // Template Ref

const doClone = (stationName, url) => {
    $cloneModal.value.create(stationName, url);
};

const {wrapWithLoading, notifySuccess} = useNotify();
const {confirmDelete} = useSweetAlert();
const {axios} = useAxios();

const doDelete = (url) => {
    confirmDelete({
        title: $gettext('Delete Station?'),
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
