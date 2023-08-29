<template>
    <card-page :title="$gettext('Stations')">
        <template #actions>
            <add-button
                :text="$gettext('Add Station')"
                @click="doCreate"
            />
        </template>

        <data-table
            id="stations"
            ref="$datatable"
            paginated
            :fields="fields"
            :api-url="listUrl"
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
        v-bind="pickProps(props, stationFormProps)"
        ref="$editModal"
        :create-url="listUrl"
        @relist="relist"
    />

    <admin-stations-clone-modal
        ref="$cloneModal"
        @relist="relist"
    />
</template>

<script setup lang="ts">
import DataTable, { DataTableField } from '~/components/Common/DataTable.vue';
import AdminStationsEditModal from "./Stations/EditModal.vue";
import {get} from "lodash";
import AdminStationsCloneModal from "./Stations/CloneModal.vue";
import stationFormProps from "./Stations/stationFormProps";
import {pickProps} from "~/functions/pickProps";
import {useTranslate} from "~/vendor/gettext";
import {ref} from "vue";
import useHasDatatable, {DataTableTemplateRef} from "~/functions/useHasDatatable";
import useHasEditModal, {EditModalTemplateRef} from "~/functions/useHasEditModal";
import useConfirmAndDelete from "~/functions/useConfirmAndDelete";
import CardPage from "~/components/Common/CardPage.vue";
import {getApiUrl} from "~/router";
import AddButton from "~/components/Common/AddButton.vue";
import CloneModal from "~/components/Admin/Stations/CloneModal.vue";

const props = defineProps({
    ...stationFormProps,
    frontendTypes: {
        type: Object,
        required: true
    },
    backendTypes: {
        type: Object,
        required: true
    }
});

const listUrl = getApiUrl('/admin/stations');

const {$gettext} = useTranslate();

const fields: DataTableField[] = [
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
            return get(props.frontendTypes, [value, 'name'], '');
        }
    },
    {
        key: 'backend_type',
        label: $gettext('AutoDJ'),
        sortable: false,
        formatter: (value) => {
            return get(props.backendTypes, [value, 'name'], '');
        }
    },
    {
        key: 'actions',
        label: $gettext('Actions'),
        sortable: false,
        class: 'shrink'
    }
];

const $datatable = ref<DataTableTemplateRef>(null);
const {relist} = useHasDatatable($datatable);

const $editModal = ref<EditModalTemplateRef>(null);
const {doCreate, doEdit} = useHasEditModal($editModal);

const $cloneModal = ref<InstanceType<typeof CloneModal> | null>(null);

const doClone = (stationName, url) => {
    $cloneModal.value.create(stationName, url);
};

const {doDelete} = useConfirmAndDelete(
    $gettext('Delete Station?'),
    relist
);
</script>
