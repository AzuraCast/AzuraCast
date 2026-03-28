<template>
    <loading :loading="propsLoading" lazy>
        <card-page :title="$gettext('Users')">
            <template #actions>
                <add-button
                    :text="$gettext('Add User')"
                    @click="doCreate"
                />
            </template>

            <data-table
                id="users"
                paginated
                :fields="fields"
                :provider="listItemProvider"
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
                            class="btn btn-secondary"
                            @click="doOpenLoginLink(row.item)"
                        >
                            {{ $gettext('Login Link') }}
                        </button>
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

        <login-link-modal
            ref="$loginLinkModal"
            :create-url="loginTokenUrl"
        />

        <edit-modal
            v-if="props"
            ref="$editModal"
            :create-url="listUrl"
            :roles="props.roles"
            @relist="() => relist()"
        />
    </loading>
</template>

<script setup lang="ts">
import DataTable, {DataTableField} from "~/components/Common/DataTable.vue";
import EditModal from "~/components/Admin/Users/EditModal.vue";
import {useTranslate} from "~/vendor/gettext";
import {useTemplateRef} from "vue";
import useHasEditModal from "~/functions/useHasEditModal";
import useConfirmAndDelete from "~/functions/useConfirmAndDelete";
import CardPage from "~/components/Common/CardPage.vue";
import AddButton from "~/components/Common/AddButton.vue";
import {useApiItemProvider} from "~/functions/dataTable/useApiItemProvider.ts";
import {QueryKeys} from "~/entities/Queries.ts";
import {useQuery} from "@tanstack/vue-query";
import {ApiAdminUserWithDetails, ApiAdminVueUsersProps} from "~/entities/ApiInterfaces.ts";
import {useAxios} from "~/vendor/axios.ts";
import Loading from "~/components/Common/Loading.vue";
import {useApiRouter} from "~/functions/useApiRouter.ts";
import LoginLinkModal from "~/components/Admin/Users/LoginLinkModal.vue";

const {getApiUrl} = useApiRouter();
const propsUrl = getApiUrl('/admin/vue/users');
const listUrl = getApiUrl('/admin/users');
const loginTokenUrl = getApiUrl('/admin/login_tokens');

const {axios} = useAxios();

const {data: props, isLoading: propsLoading} = useQuery<ApiAdminVueUsersProps>({
    queryKey: [QueryKeys.AdminUsers, 'props'],
    queryFn: async ({signal}) => {
        const {data} = await axios.get<ApiAdminVueUsersProps>(propsUrl.value, {signal});
        return data;
    }
});

const {$gettext} = useTranslate();

type Row = Required<ApiAdminUserWithDetails>;

const fields: DataTableField<Row>[] = [
    {key: 'name', isRowHeader: true, label: $gettext('User Name'), sortable: true},
    {key: 'roles', label: $gettext('Roles'), sortable: false},
    {key: 'actions', label: $gettext('Actions'), sortable: false, class: 'shrink'}
];

const listItemProvider = useApiItemProvider<Row>(
    listUrl,
    [
        QueryKeys.AdminUsers,
        'data'
    ]
);

const relist = () => {
    void listItemProvider.refresh();
}

const $editModal = useTemplateRef('$editModal');
const {doCreate, doEdit} = useHasEditModal($editModal);

const $loginLinkModal = useTemplateRef('$loginLinkModal');
const doOpenLoginLink = (user: Row) => {
    $loginLinkModal.value?.create(user.id);
};

const {doDelete} = useConfirmAndDelete(
    $gettext('Delete User?'),
    () => relist()
);
</script>
