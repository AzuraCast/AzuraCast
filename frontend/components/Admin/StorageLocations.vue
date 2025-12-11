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
            :show-toolbar="false"
            :fields="fields"
            :provider="listItemProvider"
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
                {{ item.stations?.join(', ') }}
            </template>
            <template #cell(space)="{item}">
                <template v-if="item.storageAvailable">
                    <div
                        class="progress h-20 mb-3"
                        role="progressbar"
                        :aria-label="item.storageUsedPercent+'%'"
                        :aria-valuenow="item.storageUsedPercent ?? undefined"
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
        @relist="() => relist()"
    />
</template>

<script setup lang="ts">
import DataTable, {DataTableField} from "~/components/Common/DataTable.vue";
import EditModal from "~/components/Admin/StorageLocations/EditModal.vue";
import {computed, ref, useTemplateRef} from "vue";
import {useTranslate} from "~/vendor/gettext";
import useHasEditModal from "~/functions/useHasEditModal";
import useConfirmAndDelete from "~/functions/useConfirmAndDelete";
import CardPage from "~/components/Common/CardPage.vue";
import AddButton from "~/components/Common/AddButton.vue";
import {
    ApiAdminStorageLocation,
    StorageLocation,
    StorageLocationAdapters,
    StorageLocationTypes
} from "~/entities/ApiInterfaces.ts";
import {useApiItemProvider} from "~/functions/dataTable/useApiItemProvider.ts";
import {QueryKeys} from "~/entities/Queries.ts";
import {useApiRouter} from "~/functions/useApiRouter.ts";

const activeType = ref<StorageLocationTypes>(StorageLocationTypes.StationMedia);

const {getApiUrl} = useApiRouter();
const listUrl = getApiUrl('/admin/storage_locations');
const listUrlForType = computed(() => {
    return listUrl.value + '?type=' + activeType.value;
});

const {$gettext} = useTranslate();

type Row = Required<StorageLocation & ApiAdminStorageLocation>;

const fields: DataTableField<Row>[] = [
    {key: 'adapter', label: $gettext('Adapter'), sortable: false},
    {key: 'space', label: $gettext('Space Used'), class: 'text-nowrap', sortable: false},
    {key: 'stations', label: $gettext('Station(s)'), sortable: false},
    {key: 'actions', label: $gettext('Actions'), class: 'shrink', sortable: false}
];

const listItemProvider = useApiItemProvider<Row>(
    listUrlForType,
    [
        QueryKeys.AdminStorageLocations,
        activeType,
    ]
);

const relist = () => {
    void listItemProvider.refresh();
}

const tabs = [
    {
        type: StorageLocationTypes.StationMedia,
        title: $gettext('Station Media')
    },
    {
        type: StorageLocationTypes.StationRecordings,
        title: $gettext('Station Recordings')
    },
    {
        type: StorageLocationTypes.StationPodcasts,
        title: $gettext('Station Podcasts'),
    },
    {
        type: StorageLocationTypes.Backup,
        title: $gettext('Backups')
    }
];

const $editModal = useTemplateRef('$editModal');
const {doCreate, doEdit} = useHasEditModal($editModal);

const setType = (type: StorageLocationTypes) => {
    activeType.value = type;
};

const getAdapterName = (adapter: StorageLocationAdapters) => {
    switch (adapter) {
        case StorageLocationAdapters.Local:
            return $gettext('Local');

        case StorageLocationAdapters.S3:
            return $gettext('Remote: S3 Compatible');

        case StorageLocationAdapters.Dropbox:
            return $gettext('Remote: Dropbox');

        case StorageLocationAdapters.Sftp:
            return $gettext('Remote: SFTP');
    }
};

const getSpaceUsed = (item: Row) => {
    return (item.storageAvailable)
        ? item.storageUsed + ' / ' + item.storageAvailable
        : item.storageUsed;
};

const getProgressVariant = (percent: number | null) => {
    if (percent === null) {
        return '';
    } else if (percent > 85) {
        return 'text-bg-danger';
    } else if (percent > 65) {
        return 'text-bg-warning';
    } else {
        return 'text-bg-primary';
    }
};

const {doDelete} = useConfirmAndDelete(
    $gettext('Delete Storage Location?'),
    () => relist()
);
</script>
