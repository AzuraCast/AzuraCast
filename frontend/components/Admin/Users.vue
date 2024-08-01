<template>
    <card-page :title="$gettext('Users')">
        <template #actions>
            <add-button
                :text="$gettext('Add User')"
                @click="doCreate"
            />
        </template>

        <data-table
            id="users"
            ref="$datatable"
            paginated
            :fields="fields"
            :api-url="listUrl"
        >
            <template #cell(name)="row">
                <h5
                    v-if="row.item.name !== ''"
                    class="mb-0"
                >
                    {{ row.item.name }}
                </h5>
                <a :href="'mailto:'+row.item.email">{{ row.item.email }}</a>
                <span
                    v-if="row.item.is_me"
                    class="badge text-bg-primary ms-1"
                >
                    {{ $gettext('You') }}
                </span>
            </template>
            <template #cell(roles)="row">
                <div
                    v-for="role in row.item.roles"
                    :key="role.id"
                >
                    {{ role.name }}
                </div>
            </template>
            <template #cell(actions)="row">
                <div
                    v-if="!row.item.is_me"
                    class="btn-group btn-group-sm"
                >
                    <a
                        class="btn btn-secondary"
                        :href="row.item.links.masquerade"
                        target="_blank"
                    >
                        {{ $gettext('Log In') }}
                    </a>
                    <button
                        type="button"
                        class="btn btn-primary"
                        @click="doEdit(row.item.links.self)"
                    >
                        {{ $gettext('Edit') }}
                    </button>
                    <button
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
        :roles="roles"
        @relist="relist"
    />
</template>

<script setup lang="ts">
import DataTable, { DataTableField } from '~/components/Common/DataTable.vue';
import EditModal from './Users/EditModal.vue';
import {useTranslate} from "~/vendor/gettext";
import {ref} from "vue";
import useHasDatatable, {DataTableTemplateRef} from "~/functions/useHasDatatable";
import useHasEditModal, {EditModalTemplateRef} from "~/functions/useHasEditModal";
import useConfirmAndDelete from "~/functions/useConfirmAndDelete";
import CardPage from "~/components/Common/CardPage.vue";
import {getApiUrl} from "~/router";
import AddButton from "~/components/Common/AddButton.vue";

const props = defineProps({
    roles: {
        type: Object,
        required: true
    }
});

const listUrl = getApiUrl('/admin/users');

const {$gettext} = useTranslate();

const fields: DataTableField[] = [
    {key: 'name', isRowHeader: true, label: $gettext('User Name'), sortable: true},
    {key: 'roles', label: $gettext('Roles'), sortable: false},
    {key: 'actions', label: $gettext('Actions'), sortable: false, class: 'shrink'}
];

const $datatable = ref<DataTableTemplateRef>(null);
const {relist} = useHasDatatable($datatable);

const $editModal = ref<EditModalTemplateRef>(null);
const {doCreate, doEdit} = useHasEditModal($editModal);

const {doDelete} = useConfirmAndDelete(
    $gettext('Delete User?'),
    relist
);
</script>
