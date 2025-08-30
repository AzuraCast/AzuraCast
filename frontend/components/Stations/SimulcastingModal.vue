<template>
    <Modal
        :show="show"
        :title="isEditing ? $gettext('Edit Simulcasting Stream') : $gettext('Add Simulcasting Stream')"
        size="lg"
        @close="handleClose"
    >
        <form @submit.prevent="handleSubmit">
            <div class="row">
                <div class="col-md-6">
                    <FormField
                        id="edit_form_name"
                        :field="v$.name"
                        :label="$gettext('Name')"
                        :description="$gettext('A name for this simulcasting stream to help you identify it.')"
                    />
                </div>
                <div class="col-md-6">
                    <FormField
                        id="edit_form_adapter"
                        :field="v$.adapter"
                        :label="$gettext('Platform')"
                        :description="$gettext('The platform to stream to.')"
                    >
                        <template #default="slotProps">
                            <select
                                :id="slotProps.id"
                                v-model="form.adapter"
                                :class="slotProps.class"
                                :aria-describedby="slotProps.ariaDescribedby"
                            >
                                <option value="">{{ $gettext('Select Platform') }}</option>
                                <option
                                    v-for="(adapter, key) in adapters"
                                    :key="key"
                                    :value="key"
                                >
                                    {{ adapter.description }}
                                </option>
                            </select>
                        </template>
                    </FormField>
                </div>
            </div>

            <FormField
                id="edit_form_stream_key"
                :field="v$.stream_key"
                :label="$gettext('Stream Key')"
                :description="$gettext('The stream key provided by the platform.')"
            />

            <div class="row">
                <div class="col-md-6">
                    <FormField
                        id="edit_form_created_at"
                        :label="$gettext('Created')"
                        :description="$gettext('When this stream was created.')"
                    >
                        <template #default>
                            <input
                                type="text"
                                class="form-control"
                                :value="formatDate(form.created_at)"
                                readonly
                                disabled
                            />
                        </template>
                    </FormField>
                </div>
                <div class="col-md-6">
                    <FormField
                        id="edit_form_updated_at"
                        :label="$gettext('Last Updated')"
                        :description="$gettext('When this stream was last modified.')"
                    >
                        <template #default>
                            <input
                                type="text"
                                class="form-control"
                                :value="formatDate(form.updated_at)"
                                readonly
                                disabled
                            />
                        </template>
                    </FormField>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <FormField
                        id="edit_form_status"
                        :label="$gettext('Status')"
                        :description="$gettext('Current status of the simulcasting stream.')"
                    >
                        <template #default>
                            <span
                                :class="`badge bg-${getStatusColor(form.status)}`"
                            >
                                {{ getStatusLabel(form.status) }}
                            </span>
                        </template>
                    </FormField>
                </div>
                <div class="col-md-6" v-if="form.error_message">
                    <FormField
                        id="edit_form_error_message"
                        :label="$gettext('Error Message')"
                        :description="$gettext('Last error that occurred with this stream.')"
                    >
                        <template #default>
                            <div class="text-danger">
                                {{ form.error_message }}
                            </div>
                        </template>
                    </FormField>
                </div>
            </div>
        </form>

        <template #footer>
            <button
                type="button"
                class="btn btn-secondary"
                @click="handleClose"
            >
                {{ $gettext('Cancel') }}
            </button>
            <button
                type="button"
                class="btn btn-primary"
                :disabled="isSubmitting || !isFormValid"
                @click="handleSubmit"
            >
                <div v-if="isSubmitting" class="spinner-border spinner-border-sm me-2" role="status">
                    <span class="visually-hidden">{{ $gettext('Loading...') }}</span>
                </div>
                {{ isEditing ? $gettext('Update') : $gettext('Create') }}
            </button>
        </template>
    </Modal>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { useVuelidate } from '@vuelidate/core'
import { required, minLength, maxLength } from '@vuelidate/validators'
import { useMutation, useQueryClient } from '@tanstack/vue-query'
import { getStationApiUrl } from '~/router'
import Modal from '~/components/Common/Modal.vue'
import FormField from '~/components/Form/FormField.vue'

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
    show: boolean
    stream?: SimulcastingStream | null
    adapters: Record<string, Adapter>
}

interface Emits {
    (e: 'update:show', value: boolean): void
    (e: 'saved', stream: SimulcastingStream): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

const queryClient = useQueryClient()
const isSubmitting = ref(false)

// Form data
const form = ref({
    name: '',
    adapter: '',
    stream_key: '',
    status: 'stopped',
    error_message: '',
    created_at: '',
    updated_at: '',
})

// Validation rules
const rules = {
    name: { required, minLength: minLength(1), maxLength: maxLength(255) },
    adapter: { required },
    stream_key: { required, minLength: minLength(1), maxLength: maxLength(500) },
}

const v$ = useVuelidate(rules, form)

// Computed properties
const isEditing = computed(() => !!props.stream)
const isFormValid = computed(() => !v$.value.$invalid)

// Watch for stream changes
watch(() => props.stream, (newStream) => {
    if (newStream) {
        form.value = { ...newStream }
    } else {
        resetForm()
    }
}, { immediate: true })

// Watch for show changes
watch(() => props.show, (newShow) => {
    if (!newShow) {
        resetForm()
    }
})

// Methods
const resetForm = (): void => {
    form.value = {
        name: '',
        adapter: '',
        stream_key: '',
        status: 'stopped',
        error_message: '',
        created_at: '',
        updated_at: '',
    }
    v$.value.$reset()
}

const handleClose = (): void => {
    emit('update:show', false)
}

const formatDate = (dateString: string): string => {
    if (!dateString) return ''
    return new Date(dateString).toLocaleString()
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

// Create mutation
const createMutation = useMutation({
    mutationFn: async (data: typeof form.value): Promise<SimulcastingStream> => {
        const response = await fetch(getStationApiUrl('/simulcasting'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                name: data.name,
                adapter: data.adapter,
                stream_key: data.stream_key,
            }),
        })
        if (!response.ok) {
            throw new Error('Failed to create simulcasting stream')
        }
        return response.json()
    },
})

// Update mutation
const updateMutation = useMutation({
    mutationFn: async (data: typeof form.value): Promise<SimulcastingStream> => {
        const response = await fetch(getStationApiUrl(`/simulcasting/${props.stream?.id}`), {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                name: data.name,
                adapter: data.adapter,
                stream_key: data.stream_key,
            }),
        })
        if (!response.ok) {
            throw new Error('Failed to update simulcasting stream')
        }
        return response.json()
    },
})

const handleSubmit = async (): Promise<void> => {
    const isValid = await v$.value.$validate()
    if (!isValid) return

    isSubmitting.value = true

    try {
        let result: SimulcastingStream

        if (isEditing.value) {
            result = await updateMutation.mutateAsync(form.value)
        } else {
            result = await createMutation.mutateAsync(form.value)
        }

        emit('saved', result)
        emit('update:show', false)
        
        // Invalidate queries to refresh the list
        queryClient.invalidateQueries({ queryKey: ['simulcasting'] })
    } catch (error) {
        console.error('Failed to save simulcasting stream:', error)
        // You might want to show an error message to the user here
    } finally {
        isSubmitting.value = false
    }
}
</script>

