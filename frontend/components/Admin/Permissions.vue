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
    </card-page>

    <edit-modal
        ref="$editModal"
        :create-url="listUrl"
        :station-permissions="stationPermissions"
        :stations="stations"
        :global-permissions="globalPermissions"
        @relist="() => relist()"
    />
</template>

<script setup lang="ts">
import DataTable, {DataTableField} from "~/components/Common/DataTable.vue";
import EditModal from "~/components/Admin/Permissions/EditModal.vue";
import {filter, get, map} from "lodash";
import {useTranslate} from "~/vendor/gettext";
import {useTemplateRef} from "vue";
import useHasEditModal from "~/functions/useHasEditModal";
import useConfirmAndDelete from "~/functions/useConfirmAndDelete";
import CardPage from "~/components/Common/CardPage.vue";
import {getApiUrl} from "~/router";
import AddButton from "~/components/Common/AddButton.vue";
import {DeepRequired} from "utility-types";
import {ApiAdminRole, GlobalPermissions, StationPermissions} from "~/entities/ApiInterfaces.ts";
import {useApiItemProvider} from "~/functions/dataTable/useApiItemProvider.ts";
import {QueryKeys} from "~/entities/Queries.ts";

const props = defineProps<{
    stations: Record<number, string>,
    globalPermissions: Record<GlobalPermissions, string>,
    stationPermissions: Record<StationPermissions, string>,
}>();

const listUrl = getApiUrl('/admin/roles');

const {$gettext} = useTranslate();

type Row = DeepRequired<ApiAdminRole>

const fields: DataTableField<Row>[] = [
    {key: 'name', isRowHeader: true, label: $gettext('Role Name'), sortable: true},
    {key: 'permissions', label: $gettext('Permissions'), sortable: false},
    {key: 'actions', label: $gettext('Actions'), sortable: false, class: 'shrink'}
];

const listItemProvider = useApiItemProvider<Row>(
    listUrl,
    [QueryKeys.AdminPermissions]
);

const {refresh: relist} = listItemProvider;

const getGlobalPermissionNames = (permissions: GlobalPermissions[]) => {
    return filter(map(permissions, (permission) => {
        return get(props.globalPermissions, permission, null);
    }));
};

const getStationPermissionNames = (permissions: StationPermissions[]) => {
    return filter(map(permissions, (permission) => {
        return get(props.stationPermissions, permission, null);
    }));
};

const getStationName = (stationId: number) => {
    return get(props.stations, stationId, null);
};

const $editModal = useTemplateRef('$editModal');
const {doCreate, doEdit} = useHasEditModal($editModal);

const {doDelete} = useConfirmAndDelete(
    $gettext('Delete Role?'),
    () => relist()
);
</script>
