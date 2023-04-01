<template>
    <section
        class="card"
        role="region"
        aria-labelledby="hdr_users"
    >
        <b-card-header header-bg-variant="primary-dark">
            <h2
                id="hdr_users"
                class="card-title"
            >
                {{ $gettext('Users') }}
            </h2>
        </b-card-header>

        <b-card-body body-class="card-padding-sm">
            <b-button
                variant="outline-primary"
                @click.prevent="doCreate"
            >
                <icon icon="add" />
                {{ $gettext('Add User') }}
            </b-button>
        </b-card-body>

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
                    class="badge badge-primary"
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
                <b-button-group
                    v-if="!row.item.is_me"
                    size="sm"
                >
                    <b-button
                        size="sm"
                        variant="secondary"
                        :href="row.item.links.masquerade"
                        target="_blank"
                    >
                        {{ $gettext('Log In') }}
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
    </section>

    <edit-modal
        ref="$editModal"
        :create-url="listUrl"
        :roles="roles"
        @relist="relist"
    />
</template>

<script setup>
import DataTable from '~/components/Common/DataTable';
import EditModal from './Users/EditModal';
import Icon from '~/components/Common/Icon';
import {useTranslate} from "~/vendor/gettext";
import {ref} from "vue";
import useHasDatatable from "~/functions/useHasDatatable";
import useHasEditModal from "~/functions/useHasEditModal";
import useConfirmAndDelete from "~/functions/useConfirmAndDelete";

const props = defineProps({
    listUrl: {
        type: String,
        required: true
    },
    roles: {
        type: Object,
        required: true
    }
});

const {$gettext} = useTranslate();

const fields = [
    {key: 'name', isRowHeader: true, label: $gettext('User Name'), sortable: true},
    {key: 'roles', label: $gettext('Roles'), sortable: false},
    {key: 'actions', label: $gettext('Actions'), sortable: false, class: 'shrink'}
];

const $datatable = ref(); // Template Ref
const {relist} = useHasDatatable($datatable);

const $editModal = ref(); // Template Ref
const {doCreate, doEdit} = useHasEditModal($editModal);

const {doDelete} = useConfirmAndDelete(
    $gettext('Delete User?'),
    relist
);
</script>
