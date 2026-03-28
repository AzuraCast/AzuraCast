<template>
    <loading :loading="propsLoading" lazy>
        <card-page :title="$gettext('Stations')">
            <template #actions>
                <add-button
                    :text="$gettext('Add Station')"
                    @click="doCreate"
                />
            </template>

            <data-table
                id="stations"
                paginated
                :fields="fields"
                :provider="listItemProvider"
            >
                <template #cell(name)="{item}">
                    <div class="typography-subheading">
                        {{ item.name }}

                        <span
                            v-if="!item.is_enabled"
                            class="badge text-bg-secondary"
                        >{{ $gettext('Disabled') }}</span>
                    </div>
                    <code>{{ item.short_name }}</code>
                </template>
                <template #cell(actions)="row">
                    <div class="btn-group btn-group-sm">
                        <a
                            class="btn btn-secondary"
                            :href="row.item.links.manage"
                            target="_blank"
                        >
                            {{ $gettext('Manage') }}
                        </a>
                        <button
                            type="button"
                            class="btn btn-secondary"
                            @click="doClone(row.item.name, row.item.links.clone)"
                        >
                            {{ $gettext('Clone') }}
                        </button>
                        <button
                            type="button"
                            class="btn btn-sm"
                            :class="(row.item.is_enabled) ? 'btn-warning' : 'btn-success'"
                            @click="doToggle(row.item)"
                        >
                            {{ (row.item.is_enabled) ? $gettext('Disable') : $gettext('Enable') }}
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

        <admin-stations-edit-modal
            v-if="props"
            v-bind="props.formProps"
            ref="$editModal"
            :create-url="listUrl"
            @relist="() => relist()"
        />

        <admin-stations-clone-modal
            ref="$cloneModal"
            @relist="() => relist()"
        />
    </loading>
</template>

<script setup lang="ts">
import DataTable, {DataTableField} from "~/components/Common/DataTable.vue";
import AdminStationsEditModal from "~/components/Admin/Stations/EditModal.vue";
import {get} from "es-toolkit/compat";
import AdminStationsCloneModal from "~/components/Admin/Stations/CloneModal.vue";
import {useTranslate} from "~/vendor/gettext";
import {useTemplateRef} from "vue";
import useHasEditModal from "~/functions/useHasEditModal";
import useConfirmAndDelete from "~/functions/useConfirmAndDelete";
import CardPage from "~/components/Common/CardPage.vue";
import AddButton from "~/components/Common/AddButton.vue";
import {useNotify} from "~/components/Common/Toasts/useNotify.ts";
import {useAxios} from "~/vendor/axios.ts";
import {useDialog} from "~/components/Common/Dialogs/useDialog.ts";
import {useApiItemProvider} from "~/functions/dataTable/useApiItemProvider.ts";
import {QueryKeys} from "~/entities/Queries.ts";
import {useQuery} from "@tanstack/vue-query";
import {ApiAdminVueStationsProps, HasLinks, Station} from "~/entities/ApiInterfaces.ts";
import Loading from "~/components/Common/Loading.vue";
import {useApiRouter} from "~/functions/useApiRouter.ts";

const {getApiUrl} = useApiRouter();
const listUrl = getApiUrl('/admin/stations');
const propsUrl = getApiUrl('/admin/vue/stations');

const {axios} = useAxios();

const {data: props, isLoading: propsLoading} = useQuery<ApiAdminVueStationsProps>({
    queryKey: [QueryKeys.AdminStations, 'props'],
    queryFn: async ({signal}) => {
        const {data} = await axios.get<ApiAdminVueStationsProps>(propsUrl.value, {signal});
        return data;
    },
});


const {$gettext} = useTranslate();

type Row = Required<Station & HasLinks>;

const fields: DataTableField<Row>[] = [
    {
        key: 'name',
        isRowHeader: true,
        label: $gettext('Name'),
        sortable: true
    },
    {
        key: 'frontend_type',
        label: $gettext('Broadcasting'),
        sortable: false,
        formatter: (value) => {
            return get(props.value?.frontendTypes, [value, 'name'], '');
        }
    },
    {
        key: 'backend_type',
        label: $gettext('AutoDJ'),
        sortable: false,
        formatter: (value) => {
            return get(props.value?.backendTypes, [value, 'name'], '');
        }
    },
    {
        key: 'actions',
        label: $gettext('Actions'),
        sortable: false,
        class: 'shrink'
    }
];

const listItemProvider = useApiItemProvider<Row>(
    listUrl,
    [
        QueryKeys.AdminStations,
        'data'
    ]
);

const relist = () => {
    void listItemProvider.refresh();
}

const $editModal = useTemplateRef('$editModal');
const {doCreate, doEdit} = useHasEditModal($editModal);

const $cloneModal = useTemplateRef('$cloneModal');

const doClone = (stationName: string, url: string) => {
    $cloneModal.value?.create(stationName, url);
};

const {showAlert} = useDialog();
const {notifySuccess} = useNotify();

const doToggle = async (station: Row) => {
    const {value} = await showAlert((station.is_enabled)
        ? {
            title: $gettext('Disable station?'),
            confirmButtonText: $gettext('Disable'),
            confirmButtonClass: 'btn-warning',
            focusCancel: true
        } : {
            title: $gettext('Enable station?'),
            confirmButtonText: $gettext('Enable'),
            confirmButtonClass: 'btn-success',
            focusCancel: false
        }
    );

    if (!value) {
        return;
    }

    const {data} = await axios.put(station.links.self, {
        is_enabled: !station.is_enabled
    });

    notifySuccess(data.message);
    relist();
};

const {doDelete} = useConfirmAndDelete(
    $gettext('Delete Station?'),
    () => relist()
);
</script>
