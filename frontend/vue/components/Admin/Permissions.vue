<template>
    <b-card no-body>
        <b-card-header header-bg-variant="primary-dark">
            <h2 class="card-title">
                {{ $gettext('Roles & Permissions') }}
            </h2>
        </b-card-header>

        <info-card>
            <p class="card-text">
                {{
                    $gettext('AzuraCast uses a role-based access control system. Roles are given permissions to certain sections of the site, then users are assigned into those roles.')
                }}
            </p>
        </info-card>

        <b-card-body body-class="card-padding-sm">
            <b-button
                variant="outline-primary"
                @click.prevent="doCreate"
            >
                <icon icon="add" />
                {{ $gettext('Add Role') }}
            </b-button>
        </b-card-body>

        <data-table
            id="permissions"
            ref="$datatable"
            paginated
            :fields="fields"
            :api-url="listUrl"
        >
            <template #cell(permissions)="row">
                <div v-if="row.item.permissions.global.length > 0">
                    {{ $gettext('Global') }}
                    :
                    {{ getGlobalPermissionNames(row.item.permissions.global).join(', ') }}
                </div>
                <div
                    v-for="(permissions, stationId) in row.item.permissions.station"
                    :key="stationId"
                >
                    <b>{{ getStationName(stationId) }}</b>:
                    {{ getStationPermissionNames(permissions).join(', ') }}
                </div>
            </template>
            <template #cell(actions)="row">
                <b-button-group
                    v-if="!row.item.is_super_admin"
                    size="sm"
                >
                    <b-button
                        size="sm"
                        variant="primary"
                        @click.prevent="doEdit(row.item.links.self)"
                    >
                        {{ $gettext('Edit') }}
                    </b-button>
                    <b-button
                        v-if="row.item.id !== 1"
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
        :station-permissions="stationPermissions"
        :stations="stations"
        :global-permissions="globalPermissions"
        @relist="relist"
    />
</template>

<script setup>
import DataTable from '~/components/Common/DataTable';
import EditModal from './Permissions/EditModal';
import Icon from '~/components/Common/Icon';
import InfoCard from '~/components/Common/InfoCard';
import {filter, get, map} from 'lodash';
import {useTranslate} from "~/vendor/gettext";
import {ref} from "vue";
import {useNotify} from "~/vendor/bootstrapVue";
import {useAxios} from "~/vendor/axios";
import {useSweetAlert} from "~/vendor/sweetalert";

const props = defineProps({
    listUrl: {
        type: String,
        required: true
    },
    stations: {
        type: Array,
        required: true
    },
    globalPermissions: {
        type: Array,
        required: true
    },
    stationPermissions: {
        type: Array,
        required: true
    }
});

const {$gettext} = useTranslate();

const fields = [
    {key: 'name', isRowHeader: true, label: $gettext('Role Name'), sortable: true},
    {key: 'permissions', label: $gettext('Permissions'), sortable: false},
    {key: 'actions', label: $gettext('Actions'), sortable: false, class: 'shrink'}
];

const getGlobalPermissionNames = (permissions) => {
    return filter(map(permissions, (permission) => {
        return get(props.globalPermissions, permission, null);
    }));
};

const getStationPermissionNames = (permissions) => {
    return filter(map(permissions, (permission) => {
        return get(props.stationPermissions, permission, null);
    }));
};

const getStationName = (stationId) => {
    return get(props.stations, stationId, null);
};

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

const {wrapWithLoading, notifySuccess} = useNotify();
const {confirmDelete} = useSweetAlert();
const {axios} = useAxios();

const doDelete = (url) => {
    confirmDelete({
        title: $gettext('Delete Role?'),
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
