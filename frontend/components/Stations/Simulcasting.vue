<template>
    <card-page :title="$gettext('Simulcasting')">
        <template #info>
            <p class="card-text">
                {{
                    $gettext('Simulcasting allows you to broadcast your radio stream to multiple platforms like Facebook Live and YouTube Live simultaneously. Each platform requires its own stream key and configuration.')
                }}
            </p>
        </template>
        <template #actions>
            <add-button
                :text="$gettext('Add Simulcasting Stream')"
                @click="doCreate"
            />
        </template>

        <data-table
            id="station_simulcasting"
            :fields="fields"
            :provider="listItemProvider"
            paginated
        >
            <template #cell(name)="row">
                <h5 class="m-0">
                    {{ row.item.name }}
                </h5>
                <div v-if="row.item.error_message" class="text-danger small">
                    {{ row.item.error_message }}
                </div>
            </template>
            <template #cell(adapter)="row">
                <span class="badge bg-secondary">{{ getAdapterLabel(row.item.adapter) }}</span>
            </template>
            <template #cell(status)="row">
                <span
                    :class="`badge bg-${getStatusColor(row.item.status)}`"
                >
                    {{ getStatusLabel(row.item.status) }}
                </span>
            </template>
            <template #cell(actions)="row">
                <div class="btn-group btn-group-sm">
                    <button
                        v-if="canStart(row.item.status)"
                        type="button"
                        class="btn btn-success"
                        :disabled="isActionLoading(row.item.id, 'start')"
                        @click="startStream(row.item)"
                    >
                        <i class="material-icons" aria-hidden="true">play_arrow</i>
                        {{ $gettext('Start') }}
                    </button>
                    
                    <button
                        v-if="canStop(row.item.status)"
                        type="button"
                        class="btn btn-warning"
                        :disabled="isActionLoading(row.item.id, 'stop')"
                        @click="stopStream(row.item)"
                    >
                        <i class="material-icons" aria-hidden="true">stop</i>
                        {{ $gettext('Stop') }}
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

    <edit-modal
        ref="$editModal"
        :create-url="listUrl"
        @relist="() => relist()"
        @needs-restart="() => mayNeedRestart()"
    />
</template>

<script setup lang="ts">
import DataTable from "~/components/Common/DataTable.vue";
import EditModal from "~/components/Stations/Simulcasting/EditModal.vue";
import {useTranslate} from "~/vendor/gettext";
import {useTemplateRef, ref} from "vue";
import {useMayNeedRestart} from "~/functions/useMayNeedRestart";
import useHasEditModal from "~/functions/useHasEditModal";
import useConfirmAndDelete from "~/functions/useConfirmAndDelete";
import CardPage from "~/components/Common/CardPage.vue";
import {getStationApiUrl} from "~/router";
import AddButton from "~/components/Common/AddButton.vue";
import {useApiItemProvider} from "~/functions/dataTable/useApiItemProvider.ts";
import {queryKeyWithStation} from "~/entities/Queries.ts";

const listUrl = getStationApiUrl('/simulcasting');

const {$gettext} = useTranslate();

const fields = [
    {key: 'name', isRowHeader: true, label: $gettext('Name'), sortable: true},
    {key: 'adapter', label: $gettext('Platform'), sortable: true},
    {key: 'status', label: $gettext('Status'), sortable: true},
    {key: 'actions', label: $gettext('Actions'), sortable: false, class: 'shrink'}
];

const listItemProvider = useApiItemProvider(
    listUrl,
    queryKeyWithStation(['simulcasting'])
);

const relist = () => {
    void listItemProvider.refresh();
}

// Helper functions
const getAdapterLabel = (adapter: string): string => {
    const adapterMap: Record<string, string> = {
        facebook: 'Facebook Live',
        youtube: 'YouTube Live',
    }
    return adapterMap[adapter] || adapter
}

const getStatusLabel = (status: string): string => {
    const statusMap: Record<string, string> = {
        stopped: 'Stopped',
        running: 'Running',
        error: 'Error',
        starting: 'Starting',
        stopping: 'Stopping',
    }
    return statusMap[status] || status
}

const getStatusColor = (status: string): string => {
    const colorMap: Record<string, string> = {
        stopped: 'secondary',
        running: 'success',
        error: 'danger',
        starting: 'warning',
        stopping: 'warning',
    }
    return colorMap[status] || 'secondary'
}

const canStart = (status: string): boolean => {
    return ['stopped', 'error'].includes(status)
}

const canStop = (status: string): boolean => {
    return ['running', 'starting'].includes(status)
}

const actionLoading = ref<Record<string, boolean>>({})

const isActionLoading = (streamId: number, action: string): boolean => {
    return actionLoading.value[`${streamId}-${action}`] || false
}

// Action handlers
const startStream = async (stream: any): Promise<void> => {
    actionLoading.value[`${stream.id}-start`] = true
    try {
        const response = await fetch(getStationApiUrl(`/simulcasting/${stream.id}/start`), {
            method: 'POST',
        })
        if (!response.ok) {
            throw new Error('Failed to start stream')
        }
        relist()
    } finally {
        actionLoading.value[`${stream.id}-start`] = false
    }
}

const stopStream = async (stream: any): Promise<void> => {
    actionLoading.value[`${stream.id}-stop`] = true
    try {
        const response = await fetch(getStationApiUrl(`/simulcasting/${stream.id}/stop`), {
            method: 'POST',
        })
        if (!response.ok) {
            throw new Error('Failed to stop stream')
        }
        relist()
    } finally {
        actionLoading.value[`${stream.id}-stop`] = false
    }
}

const $editModal = useTemplateRef('$editModal');
const {doCreate, doEdit} = useHasEditModal($editModal);

const {mayNeedRestart} = useMayNeedRestart();

const {doDelete} = useConfirmAndDelete(
    $gettext('Delete Simulcasting Stream?'),
    () => {
        mayNeedRestart();
        relist();
    }
);
</script>

<style lang="scss" scoped>
.btn-group {
    .btn {
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
    }
}

.badge {
    font-size: 0.75rem;
}

.table {
    th {
        border-top: none;
        font-weight: 600;
    }
}
</style>

