<template>
    <div id="station_simulcasting">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="h3 mb-0">{{ $gettext('Simulcasting') }}</h1>
            <button
                type="button"
                class="btn btn-primary"
                @click="showCreateModal = true"
            >
                <i class="material-icons" aria-hidden="true">add</i>
                {{ $gettext('Add Simulcasting Stream') }}
            </button>
        </div>

        <div v-if="isLoading" class="text-center py-4">
            <div class="spinner-border" role="status">
                <span class="visually-hidden">{{ $gettext('Loading...') }}</span>
            </div>
        </div>

        <div v-else-if="error" class="alert alert-danger">
            {{ error }}
        </div>

        <div v-else-if="simulcastingStreams.length === 0" class="text-center py-4">
            <p class="text-muted">{{ $gettext('No simulcasting streams configured.') }}</p>
            <button
                type="button"
                class="btn btn-primary"
                @click="showCreateModal = true"
            >
                {{ $gettext('Add Your First Stream') }}
            </button>
        </div>

        <div v-else class="card">
            <div class="table-responsive">
                <table class="table table-striped table-hover mb-0">
                    <thead>
                        <tr>
                            <th>{{ $gettext('Name') }}</th>
                            <th>{{ $gettext('Adapter') }}</th>
                            <th>{{ $gettext('Status') }}</th>
                            <th>{{ $gettext('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="stream in simulcastingStreams"
                            :key="stream.id"
                        >
                            <td>
                                <strong>{{ stream.name }}</strong>
                                <div v-if="stream.error_message" class="text-danger small">
                                    {{ stream.error_message }}
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-secondary">{{ getAdapterLabel(stream.adapter) }}</span>
                            </td>
                            <td>
                                <span
                                    :class="`badge bg-${getStatusColor(stream.status)}`"
                                >
                                    {{ getStatusLabel(stream.status) }}
                                </span>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <button
                                        v-if="canStart(stream.status)"
                                        type="button"
                                        class="btn btn-success"
                                        :disabled="isActionLoading(stream.id, 'start')"
                                        @click="startStream(stream)"
                                    >
                                        <i class="material-icons" aria-hidden="true">play_arrow</i>
                                        {{ $gettext('Start') }}
                                    </button>
                                    
                                    <button
                                        v-if="canStop(stream.status)"
                                        type="button"
                                        class="btn btn-warning"
                                        :disabled="isActionLoading(stream.id, 'stop')"
                                        @click="stopStream(stream)"
                                    >
                                        <i class="material-icons" aria-hidden="true">stop</i>
                                        {{ $gettext('Stop') }}
                                    </button>
                                    
                                    <button
                                        type="button"
                                        class="btn btn-primary"
                                        @click="editStream(stream)"
                                    >
                                        <i class="material-icons" aria-hidden="true">edit</i>
                                        {{ $gettext('Edit') }}
                                    </button>
                                    
                                    <button
                                        type="button"
                                        class="btn btn-danger"
                                        @click="deleteStream(stream)"
                                    >
                                        <i class="material-icons" aria-hidden="true">delete</i>
                                        {{ $gettext('Delete') }}
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Create/Edit Modal -->
        <SimulcastingModal
            v-model:show="showCreateModal"
            :stream="editingStream"
            :adapters="availableAdapters"
            @saved="handleStreamSaved"
        />

        <!-- Delete Confirmation Modal -->
        <ConfirmModal
            v-model:show="showDeleteModal"
            :title="$gettext('Delete Simulcasting Stream')"
            :message="$gettext('Are you sure you want to delete this simulcasting stream? This action cannot be undone.')"
            :confirm-text="$gettext('Delete')"
            confirm-variant="danger"
            @confirm="confirmDelete"
        />
    </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useQuery, useMutation, useQueryClient } from '@tanstack/vue-query'
import { getStationApiUrl } from '~/router'
import SimulcastingModal from './SimulcastingModal.vue'
import ConfirmModal from '~/components/Common/ConfirmModal.vue'

interface SimulcastingStream {
    id: number
    name: string
    adapter: string
    stream_key: string
    status: string
    error_message?: string
    created_at: string
    updated_at: string
}

interface Adapter {
    name: string
    description: string
}

interface Props {
    stationId: number
}

const props = defineProps<Props>()

const showCreateModal = ref(false)
const showDeleteModal = ref(false)
const editingStream = ref<SimulcastingStream | null>(null)
const streamToDelete = ref<SimulcastingStream | null>(null)
const actionLoading = ref<Record<string, boolean>>({})

const queryClient = useQueryClient()

// Fetch simulcasting streams
const { data: simulcastingStreams = [], isLoading, error } = useQuery({
    queryKey: ['simulcasting', props.stationId],
    queryFn: async (): Promise<SimulcastingStream[]> => {
        const response = await fetch(getStationApiUrl(`/simulcasting`))
        if (!response.ok) {
            throw new Error('Failed to fetch simulcasting streams')
        }
        return response.json()
    },
})

// Fetch available adapters
const { data: availableAdapters = {} } = useQuery({
    queryKey: ['simulcasting-adapters', props.stationId],
    queryFn: async (): Promise<Record<string, Adapter>> => {
        const response = await fetch(getStationApiUrl(`/simulcasting/adapters`))
        if (!response.ok) {
            throw new Error('Failed to fetch adapters')
        }
        return response.json()
    },
})

// Start stream mutation
const startMutation = useMutation({
    mutationFn: async (stream: SimulcastingStream): Promise<void> => {
        const response = await fetch(getStationApiUrl(`/simulcasting/${stream.id}/start`), {
            method: 'POST',
        })
        if (!response.ok) {
            throw new Error('Failed to start stream')
        }
    },
    onSuccess: () => {
        queryClient.invalidateQueries({ queryKey: ['simulcasting', props.stationId] })
    },
})

// Stop stream mutation
const stopMutation = useMutation({
    mutationFn: async (stream: SimulcastingStream): Promise<void> => {
        const response = await fetch(getStationApiUrl(`/simulcasting/${stream.id}/stop`), {
            method: 'POST',
        })
        if (!response.ok) {
            throw new Error('Failed to stop stream')
        }
    },
    onSuccess: () => {
        queryClient.invalidateQueries({ queryKey: ['simulcasting', props.stationId] })
    },
})

// Delete stream mutation
const deleteMutation = useMutation({
    mutationFn: async (stream: SimulcastingStream): Promise<void> => {
        const response = await fetch(getStationApiUrl(`/simulcasting/${stream.id}`), {
            method: 'DELETE',
        })
        if (!response.ok) {
            throw new Error('Failed to delete stream')
        }
    },
    onSuccess: () => {
        queryClient.invalidateQueries({ queryKey: ['simulcasting', props.stationId] })
        showDeleteModal.value = false
        streamToDelete.value = null
    },
})

// Helper functions
const getAdapterLabel = (adapter: string): string => {
    return availableAdapters[adapter]?.description || adapter
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

const isActionLoading = (streamId: number, action: string): boolean => {
    return actionLoading.value[`${streamId}-${action}`] || false
}

// Action handlers
const startStream = async (stream: SimulcastingStream): Promise<void> => {
    actionLoading.value[`${stream.id}-start`] = true
    try {
        await startMutation.mutateAsync(stream)
    } finally {
        actionLoading.value[`${stream.id}-start`] = false
    }
}

const stopStream = async (stream: SimulcastingStream): Promise<void> => {
    actionLoading.value[`${stream.id}-stop`] = true
    try {
        await stopMutation.mutateAsync(stream)
    } finally {
        actionLoading.value[`${stream.id}-stop`] = false
    }
}

const editStream = (stream: SimulcastingStream): void => {
    editingStream.value = { ...stream }
    showCreateModal.value = true
}

const deleteStream = (stream: SimulcastingStream): void => {
    streamToDelete.value = stream
    showDeleteModal.value = true
}

const confirmDelete = async (): Promise<void> => {
    if (streamToDelete.value) {
        await deleteMutation.mutateAsync(streamToDelete.value)
    }
}

const handleStreamSaved = (): void => {
    showCreateModal.value = false
    editingStream.value = null
    queryClient.invalidateQueries({ queryKey: ['simulcasting', props.stationId] })
}

// Auto-refresh status every 30 seconds
onMounted(() => {
    const interval = setInterval(() => {
        queryClient.invalidateQueries({ queryKey: ['simulcasting', props.stationId] })
    }, 30000)

    return () => clearInterval(interval)
})
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

