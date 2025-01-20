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
import DataTable, {DataTableField} from '~/components/Common/DataTable.vue';
import EditModal from './Permissions/EditModal.vue';
import {filter, get, map} from 'lodash';
import {useTranslate} from "~/vendor/gettext";
import {ref} from "vue";
import useHasDatatable, {DataTableTemplateRef} from "~/functions/useHasDatatable";
import useHasEditModal from "~/functions/useHasEditModal";
import useConfirmAndDelete from "~/functions/useConfirmAndDelete";
import CardPage from "~/components/Common/CardPage.vue";
import {getApiUrl} from "~/router";
import AddButton from "~/components/Common/AddButton.vue";

const props = defineProps<{
    stations: Record<string, string>,
    globalPermissions: Record<string, string>,
    stationPermissions: Record<string, string>,
}>();

const listUrl = getApiUrl('/admin/roles');

const {$gettext} = useTranslate();

const fields: DataTableField[] = [
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

const $datatable = ref<DataTableTemplateRef>(null);
const {relist} = useHasDatatable($datatable);

const $editModal = ref<InstanceType<typeof EditModal> | null>(null);
const {doCreate, doEdit} = useHasEditModal($editModal);

const {doDelete} = useConfirmAndDelete(
    $gettext('Delete Role?'),
    relist
);
</script>
