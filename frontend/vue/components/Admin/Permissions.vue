<template>
    <card-page :title="$gettext('Roles & Permissions')">
        <template #info>
            <p class="card-text">
                {{
                    $gettext('AzuraCast uses a role-based access control system. Roles are given permissions to certain sections of the site, then users are assigned into those roles.')
                }}
            </p>
        </template>
        <template #actions>
            <button
                type="button"
                class="btn btn-primary"
                @click="doCreate"
            >
                <icon icon="add" />
                <span>
                    {{ $gettext('Add Role') }}
                </span>
            </button>
        </template>

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
                <div
                    v-if="!row.item.is_super_admin"
                    class="btn-group btn-group-sm"
                >
                    <button
                        type="button"
                        class="btn btn-primary"
                        @click="doEdit(row.item.links.self)"
                    >
                        {{ $gettext('Edit') }}
                    </button>
                    <button
                        v-if="row.item.id !== 1"
                        type="button"
                        class="btn btn-danger"
                        @click="doDelete(row.item.links.self)"
                    >
                        {{ $gettext('Delete') }}
                    </button>
                </div>
            </template>
        </data-table>
    </card-page>

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
import {filter, get, map} from 'lodash';
import {useTranslate} from "~/vendor/gettext";
import {ref} from "vue";
import useHasDatatable from "~/functions/useHasDatatable";
import useHasEditModal from "~/functions/useHasEditModal";
import useConfirmAndDelete from "~/functions/useConfirmAndDelete";
import CardPage from "~/components/Common/CardPage.vue";
import {getApiUrl} from "~/router";

const props = defineProps({
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

const listUrl = getApiUrl('/admin/roles');

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
const {relist} = useHasDatatable($datatable);

const $editModal = ref(); // Template Ref
const {doCreate, doEdit} = useHasEditModal($editModal);

const {doDelete} = useConfirmAndDelete(
    $gettext('Delete Role?'),
    relist
);
</script>
