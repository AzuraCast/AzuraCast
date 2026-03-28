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
            paginated
            :fields="fields"
            :provider="listItemProvider"
        >
            <template #cell(permissions)="{item}">
                <div v-if="item.permissions.global.length > 0">
                    <b>{{ $gettext('Global') }}:</b>
                    {{ getGlobalPermissionNames(item.permissions.global).join(', ') }}
                </div>
                <div
                    v-for="(stationRow) in item.permissions.station"
                    :key="stationRow.id"
                >
                    <b>{{ getStationName(stationRow.id) }}:</b>
                    {{ getStationPermissionNames(stationRow.permissions).join(', ') }}
                </div>
                <div v-if="item.permissions.global.length === 0 && item.permissions.station.length === 0">
                    &nbsp;
                </div>
            </template>
            <template #cell(actions)="{item}">
                <div
                    v-if="!item.is_super_admin"
                    class="btn-group btn-group-sm"
                >
                    <button
                        type="button"
                        class="btn btn-primary"
                        @click="doEdit(item.links.self)"
                    >
                        {{ $gettext('Edit') }}
                    </button>
                    <button
                        v-if="item.id !== 1"
                        type="button"
                        class="btn btn-danger"
                        @click="doDelete(item.links.self)"
                    >
                        {{ $gettext('Delete') }}
                    </button>
                </div>
            </template>
        </data-table>

        <edit-modal
            ref="$editModal"
            :create-url="listUrl"
            :station-permissions="props.stationPermissions"
            :stations="props.stations"
            :global-permissions="props.globalPermissions"
            @relist="() => relist()"
        />
    </card-page>
</template>

<script setup lang="ts">
import DataTable, {DataTableField} from "~/components/Common/DataTable.vue";
import EditModal from "~/components/Admin/Permissions/EditModal.vue";
import {isEmpty} from "es-toolkit/compat";
import {useTranslate} from "~/vendor/gettext";
import {useTemplateRef} from "vue";
import useHasEditModal from "~/functions/useHasEditModal";
import useConfirmAndDelete from "~/functions/useConfirmAndDelete";
import CardPage from "~/components/Common/CardPage.vue";
import AddButton from "~/components/Common/AddButton.vue";
import {ApiAdminVuePermissionsProps, GlobalPermissions, StationPermissions} from "~/entities/ApiInterfaces.ts";
import {useApiItemProvider} from "~/functions/dataTable/useApiItemProvider.ts";
import {QueryKeys} from "~/entities/Queries.ts";
import {AdminRoleRequired} from "~/entities/AdminPermissions.ts";
import {useApiRouter} from "~/functions/useApiRouter.ts";

const props = defineProps<ApiAdminVuePermissionsProps>();

const {getApiUrl} = useApiRouter();
const listUrl = getApiUrl('/admin/roles');

const {$gettext} = useTranslate();

const fields: DataTableField<AdminRoleRequired>[] = [
    {key: 'name', isRowHeader: true, label: $gettext('Role Name'), sortable: true},
    {key: 'permissions', label: $gettext('Permissions'), sortable: false},
    {key: 'actions', label: $gettext('Actions'), sortable: false, class: 'shrink'}
];

const listItemProvider = useApiItemProvider<AdminRoleRequired>(
    listUrl,
    [QueryKeys.AdminPermissions]
);

const relist = () => {
    void listItemProvider.refresh();
}

const getGlobalPermissionNames = (permissions: GlobalPermissions[]) => {
    return permissions.map(
        (permission) => props.globalPermissions[permission] ?? null
    ).filter(
        (row) => !isEmpty(row)
    );
};

const getStationPermissionNames = (permissions: StationPermissions[]) => {
    return permissions.map(
        (permission) => props.stationPermissions[permission] ?? null
    ).filter(
        (row) => !isEmpty(row)
    );
};

const getStationName = (stationId: number) => {
    return props.stations[stationId] ?? null;
};

const $editModal = useTemplateRef('$editModal');
const {doCreate, doEdit} = useHasEditModal($editModal);

const {doDelete} = useConfirmAndDelete(
    $gettext('Delete Role?'),
    () => relist()
);
</script>
