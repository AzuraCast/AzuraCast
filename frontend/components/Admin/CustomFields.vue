<template>
    <loading :loading="propsLoading" lazy>
        <card-page :title="$gettext('Custom Fields')">
            <template #info>
                <p class="card-text">
                    {{
                        $gettext('Create custom fields to store extra metadata about each media file uploaded to your station libraries.')
                    }}
                </p>
            </template>
            <template #actions>
                <add-button
                    :text="$gettext('Add Custom Field')"
                    @click="doCreate"
                />
            </template>

            <data-table
                id="custom_fields"
                :fields="fields"
                :show-toolbar="false"
                :provider="itemProvider"
            >
                <template #cell(name)="{ item }">
                    {{ item.name }} <code>{{ item.short_name }}</code>
                </template>
                <template #cell(actions)="{ item }">
                    <div class="btn-group btn-group-sm">
                        <button
                            type="button"
                            class="btn btn-primary"
                            @click="doEdit(item.links.self)"
                        >
                            {{ $gettext('Edit') }}
                        </button>
                        <button
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
            v-if="props"
            :create-url="listUrl"
            :auto-assign-types="props.autoAssignTypes"
            @relist="() => relist()"
        />
    </loading>
</template>

<script setup lang="ts">
import DataTable, {DataTableField} from "~/components/Common/DataTable.vue";
import EditModal from "~/components/Admin/CustomFields/EditModal.vue";
import {useTranslate} from "~/vendor/gettext";
import {useTemplateRef} from "vue";
import useHasEditModal from "~/functions/useHasEditModal";
import useConfirmAndDelete from "~/functions/useConfirmAndDelete";
import CardPage from "~/components/Common/CardPage.vue";
import AddButton from "~/components/Common/AddButton.vue";
import {ApiAdminVueCustomFieldProps, CustomField, HasLinks} from "~/entities/ApiInterfaces.ts";
import {useApiItemProvider} from "~/functions/dataTable/useApiItemProvider.ts";
import {QueryKeys} from "~/entities/Queries.ts";
import {useQuery} from "@tanstack/vue-query";
import {useAxios} from "~/vendor/axios.ts";
import Loading from "~/components/Common/Loading.vue";
import {useApiRouter} from "~/functions/useApiRouter.ts";

const {getApiUrl} = useApiRouter();
const propsUrl = getApiUrl('/admin/vue/custom_fields');
const listUrl = getApiUrl('/admin/custom_fields');

const {axios} = useAxios();

const {data: props, isLoading: propsLoading} = useQuery<ApiAdminVueCustomFieldProps>({
    queryKey: [QueryKeys.AdminCustomFields, 'props'],
    queryFn: async ({signal}) => {
        const {data} = await axios.get<ApiAdminVueCustomFieldProps>(propsUrl.value, {signal});
        return data;
    }
});

const {$gettext} = useTranslate();

type Row = Required<CustomField & HasLinks>

const fields: DataTableField<Row>[] = [
    {
        key: 'name',
        isRowHeader: true,
        label: $gettext('Field Name'),
        sortable: false
    },
    {
        key: 'auto_assign',
        label: $gettext('Auto-Assign Value'),
        sortable: false,
        formatter: (value) => {
            return props.value?.autoAssignTypes[value] ?? $gettext('None');
        }
    },
    {
        key: 'actions',
        label: $gettext('Actions'),
        sortable: false,
        class: 'shrink'
    }
];

const itemProvider = useApiItemProvider(
    listUrl,
    [
        QueryKeys.AdminCustomFields,
        'data'
    ]
);

const relist = () => {
    void itemProvider.refresh();
};

const $editModal = useTemplateRef('$editModal');
const {doCreate, doEdit} = useHasEditModal($editModal);

const {doDelete} = useConfirmAndDelete(
    $gettext('Delete Custom Field?'),
    () => relist(),
);
</script>
