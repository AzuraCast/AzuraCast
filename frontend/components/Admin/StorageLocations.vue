<template>
    <card-page
        header-id="hdr_storage_locations"
        :title="$gettext('Storage Locations')"
    >
        <div class="card-body pb-0">
            <nav
                class="nav nav-tabs"
                role="tablist"
            >
                <div
                    v-for="tab in tabs"
                    :key="tab.type"
                    class="nav-item"
                    role="presentation"
                >
                    <button
                        class="nav-link"
                        :class="(activeType === tab.type) ? 'active' : ''"
                        type="button"
                        role="tab"
                        @click="setType(tab.type)"
                    >
                        {{ tab.title }}
                    </button>
                </div>
            </nav>
        </div>

        <div class="card-body buttons">
            <add-button
                :text="$gettext('Add Storage Location')"
                @click="doCreate"
            />
        </div>

        <data-table
            id="admin_storage_locations"
            ref="$dataTable"
            :show-toolbar="false"
            :fields="fields"
            :api-url="listUrlForType"
        >
            <template #cell(actions)="{item}">
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
            <template #cell(adapter)="{item}">
                <h5 class="m-0">
                    {{ getAdapterName(item.adapter) }}
                </h5>
                <p class="card-text">
                    {{ item.uri }}
                </p>
            </template>
            <template #cell(stations)="{item}">
                {{ item.stations.join(', ') }}
            </template>
            <template #cell(space)="{item}">
                <template v-if="item.storageAvailable">
                    <div
                        class="progress h-20 mb-3"
                        role="progressbar"
                        :aria-label="item.storageUsedPercent+'%'"
                        :aria-valuenow="item.storageUsedPercent"
                        aria-valuemin="0"
                        aria-valuemax="100"
                    >
                        <div
                            class="progress-bar"
                            :class="getProgressVariant(item.storageUsedPercent)"
                            :style="{ width: item.storageUsedPercent+'%' }"
                        >
                            {{ item.storageUsedPercent }}%
                        </div>
                    </div>
                </template>

                {{ getSpaceUsed(item) }}
            </template>
        </data-table>
    </card-page>

    <edit-modal
        ref="$editModal"
        :create-url="listUrl"
        :type="activeType"
        @relist="relist"
    />
</template>

<script setup lang="ts">
import DataTable, {DataTableField} from "~/components/Common/DataTable.vue";
import EditModal from "~/components/Admin/StorageLocations/EditModal.vue";
import {computed, nextTick, ref, useTemplateRef} from "vue";
import {useTranslate} from "~/vendor/gettext";
import useHasDatatable from "~/functions/useHasDatatable";
import useHasEditModal from "~/functions/useHasEditModal";
import useConfirmAndDelete from "~/functions/useConfirmAndDelete";
import CardPage from "~/components/Common/CardPage.vue";
import {getApiUrl} from "~/router";
import AddButton from "~/components/Common/AddButton.vue";
import {ApiAdminStorageLocation} from "~/entities/ApiInterfaces.ts";

const activeType = ref('station_media');

const listUrl = getApiUrl('/admin/storage_locations');
const listUrlForType = computed(() => {
    return listUrl.value + '?type=' + activeType.value;
});

const {$gettext} = useTranslate();

const fields: DataTableField<ApiAdminStorageLocation>[] = [
    {key: 'adapter', label: $gettext('Adapter'), sortable: false},
    {key: 'space', label: $gettext('Space Used'), class: 'text-nowrap', sortable: false},
    {key: 'stations', label: $gettext('Station(s)'), sortable: false},
    {key: 'actions', label: $gettext('Actions'), class: 'shrink', sortable: false}
];

const tabs = [
    {
        type: 'station_media',
        title: $gettext('Station Media')
    },
    {
        type: 'station_recordings',
        title: $gettext('Station Recordings')
    },
    {
        type: 'station_podcasts',
        title: $gettext('Station Podcasts'),
    },
    {
        type: 'backup',
        title: $gettext('Backups')
    }
];

const $dataTable = useTemplateRef('$dataTable');
const {relist} = useHasDatatable($dataTable);

const $editModal = useTemplateRef('$editModal');
const {doCreate, doEdit} = useHasEditModal($editModal);

const setType = (type: string) => {
    activeType.value = type;
    void nextTick(relist);
};

const getAdapterName = (adapter: string) => {
    switch (adapter) {
        case 'local':
            return $gettext('Local');

        case 's3':
            return $gettext('Remote: S3 Compatible');

        case 'dropbox':
            return $gettext('Remote: Dropbox');

        case 'sftp':
            return $gettext('Remote: SFTP');
    }
};

const getSpaceUsed = (item: ApiAdminStorageLocation) => {
    return (item.storageAvailable)
        ? item.storageUsed + ' / ' + item.storageAvailable
        : item.storageUsed;
};

const getProgressVariant = (percent: number) => {
    if (percent > 85) {
        return 'text-bg-danger';
    } else if (percent > 65) {
        return 'text-bg-warning';
    } else {
        return 'text-bg-primary';
    }
};

const {doDelete} = useConfirmAndDelete(
    $gettext('Delete Storage Location?'),
    relist
);
</script>
