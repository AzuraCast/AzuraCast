<template>
    <b-card no-body>
        <b-card-header header-bg-variant="primary-dark">
            <h2 class="card-title">
                {{ $gettext('Storage Locations') }}
            </h2>
        </b-card-header>
        <b-tabs
            pills
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

        <b-card-body body-class="card-padding-sm">
            <b-button
                variant="outline-primary"
                @click.prevent="doCreate"
            >
                <icon icon="add" />
                {{ $gettext('Add Storage Location') }}
            </b-button>
        </b-card-body>

        <data-table
            id="admin_storage_locations"
            ref="$datatable"
            :show-toolbar="false"
            :fields="fields"
            :responsive="false"
            :api-url="listUrlForType"
        >
            <template #cell(actions)="row">
                <b-button-group size="sm">
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
    </b-card>

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
import {useNotify} from "~/vendor/bootstrapVue";
import {useAxios} from "~/vendor/axios";
import {useSweetAlert} from "~/vendor/sweetalert";

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

const relist = () => {
    $datatable.value?.refresh();
};

const $editModal = ref(); // Template Ref

const doCreate = () => {
    $editModal.value?.create();
};

const doEdit = (url) => {
    $editModal.value?.edit(url);
};

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

const {notifySuccess, wrapWithLoading} = useNotify();
const {confirmDelete} = useSweetAlert();
const {axios} = useAxios();

const doDelete = (url) => {
    confirmDelete({
        title: $gettext('Delete Storage Location?'),
    }).then((result) => {
        if (result.value) {
            wrapWithLoading(
                axios.delete(url)
            ).then((resp) => {
                notifySuccess(resp.data.message);
                relist();
            });
        }
    });
};
</script>
