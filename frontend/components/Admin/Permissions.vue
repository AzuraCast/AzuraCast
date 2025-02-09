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
            <add-button
                :text="$gettext('Add Role')"
                @click="doCreate"
            />
        </template>

        <data-table
            id="permissions"
            ref="$dataTable"
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
                    v-for="(stationRow) in row.item.permissions.station"
                    :key="stationRow.id"
                >
                    <b>{{ getStationName(stationRow.id) }}</b>:
                    {{ getStationPermissionNames(stationRow.permissions).join(', ') }}
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

<script setup lang="ts">
import DataTable, {DataTableField} from "~/components/Common/DataTable.vue";
import EditModal from "~/components/Admin/Permissions/EditModal.vue";
import {filter, get, map} from "lodash";
import {useTranslate} from "~/vendor/gettext";
import {useTemplateRef} from "vue";
import useHasDatatable from "~/functions/useHasDatatable";
import useHasEditModal from "~/functions/useHasEditModal";
import useConfirmAndDelete from "~/functions/useConfirmAndDelete";
import CardPage from "~/components/Common/CardPage.vue";
import {getApiUrl} from "~/router";
import AddButton from "~/components/Common/AddButton.vue";
import {GlobalPermission, StationPermission} from "~/acl.ts";

const props = defineProps<{
    stations: Record<number, string>,
    globalPermissions: Record<GlobalPermission, string>,
    stationPermissions: Record<StationPermission, string>,
}>();

const listUrl = getApiUrl('/admin/roles');

const {$gettext} = useTranslate();

const fields: DataTableField[] = [
    {key: 'name', isRowHeader: true, label: $gettext('Role Name'), sortable: true},
    {key: 'permissions', label: $gettext('Permissions'), sortable: false},
    {key: 'actions', label: $gettext('Actions'), sortable: false, class: 'shrink'}
];

const getGlobalPermissionNames = (permissions: GlobalPermission[]) => {
    return filter(map(permissions, (permission) => {
        return get(props.globalPermissions, permission, null);
    }));
};

const getStationPermissionNames = (permissions: StationPermission[]) => {
    return filter(map(permissions, (permission) => {
        return get(props.stationPermissions, permission, null);
    }));
};

const getStationName = (stationId: number) => {
    return get(props.stations, stationId, null);
};

const $dataTable = useTemplateRef('$dataTable');
const {relist} = useHasDatatable($dataTable);

const $editModal = useTemplateRef('$editModal');
const {doCreate, doEdit} = useHasEditModal($editModal);

const {doDelete} = useConfirmAndDelete(
    $gettext('Delete Role?'),
    relist
);
</script>
