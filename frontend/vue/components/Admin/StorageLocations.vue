<template>
    <section
        class="card"
        role="region"
        aria-labelledby="hdr_storage_locations"
    >
        <div class="card-header text-bg-primary">
            <h2
                id="hdr_storage_locations"
                class="card-title"
            >
                {{ $gettext('Storage Locations') }}
            </h2>
        </div>

        <b-tabs
            card
            lazy
        >
            <b-tab
                v-for="tab in tabs"
                :key="tab.type"
                :active="activeType === tab.type"
                :title="tab.title"
                no-body
                @click="setType(tab.type)"
            />
        </b-tabs>

        <div class="card-body buttons">
            <button
                class="btn btn-primary"
                @click.prevent="doCreate"
            >
                <icon icon="add" />
                <span>
                    {{ $gettext('Add Storage Location') }}
                </span>
            </button>
        </div>

        <data-table
            id="admin_storage_locations"
            ref="$datatable"
            :show-toolbar="false"
            :fields="fields"
            :responsive="false"
            :api-url="listUrlForType"
        >
            <template #cell(actions)="row">
                <div class="btn-group btn-group-sm">
                    <button
                        class="btn btn-primary"
                        @click.prevent="doEdit(row.item.links.self)"
                    >
                        {{ $gettext('Edit') }}
                    </button>
                    <button
                        class="btn btn-danger"
                        @click.prevent="doDelete(row.item.links.self)"
                    >
                        {{ $gettext('Delete') }}
                    </button>
                </div>
            </template>
            <template #cell(adapter)="row">
                <h5 class="m-0">
                    {{ getAdapterName(row.item.adapter) }}
                </h5>
                <p class="card-text">
                    {{ row.item.uri }}
                </p>
            </template>
            <template #cell(space)="row">
                <template v-if="row.item.storageAvailable">
                    <b-progress
                        :value="row.item.storageUsedPercent"
                        show-progress
                        height="15px"
                        class="mb-1"
                        :variant="getProgressVariant(row.item.storageUsedPercent)"
                    />

                    {{ getSpaceUsed(row.item) }}
                </template>
                <template v-else>
                    {{ getSpaceUsed(row.item) }}
                </template>
            </template>
            <template #cell(stations)="row">
                {{ row.item.stations.join(', ') }}
            </template>
        </data-table>
    </section>

    <edit-modal
        ref="$editModal"
        :create-url="listUrl"
        :type="activeType"
        @relist="relist"
    />
</template>

<script setup>
import DataTable from '~/components/Common/DataTable';
import EditModal from './StorageLocations/EditModal';
import Icon from '~/components/Common/Icon';
import {computed, ref} from "vue";
import {useTranslate} from "~/vendor/gettext";
import useHasDatatable from "~/functions/useHasDatatable";
import useHasEditModal from "~/functions/useHasEditModal";
import useConfirmAndDelete from "~/functions/useConfirmAndDelete";

const props = defineProps({
    listUrl: {
        type: String,
        required: true
    }
});

const activeType = ref('station_media');

const listUrlForType = computed(() => {
    return props.listUrl + '?type=' + activeType.value;
});

const {$gettext} = useTranslate();

const fields = [
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

const $datatable = ref(); // Template Ref
const {relist} = useHasDatatable($datatable);

const $editModal = ref(); // Template Ref
const {doCreate, doEdit} = useHasEditModal($editModal);

const setType = (type) => {
    activeType.value = type;
    relist();
};

const getAdapterName = (adapter) => {
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

const getSpaceUsed = (item) => {
    return (item.storageAvailable)
        ? item.storageUsed + ' / ' + item.storageAvailable
        : item.storageUsed;
};

const getProgressVariant = (percent) => {
    if (percent > 85) {
        return 'danger';
    } else if (percent > 65) {
        return 'warning';
    } else {
        return 'default';
    }
};

const {doDelete} = useConfirmAndDelete(
    $gettext('Delete Storage Location?'),
    relist
);
</script>
