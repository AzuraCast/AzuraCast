<template>
    <Modal
        :show="show"
        :title="title"
        size="sm"
        @close="handleClose"
    >
        <div class="text-center">
            <p class="mb-0">{{ message }}</p>
        </div>

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
                :class="`btn btn-${confirmVariant}`"
                @click="handleConfirm"
            >
                {{ confirmText }}
            </button>
        </template>
    </Modal>
</template>

<script setup lang="ts">
import { watch } from 'vue'
import Modal from './Modal.vue'

interface Props {
    show: boolean
    title: string
    message: string
    confirmText?: string
    confirmVariant?: string
}

interface Emits {
    (e: 'update:show', value: boolean): void
    (e: 'confirm'): void
}

const props = withDefaults(defineProps<Props>(), {
    confirmText: 'Confirm',
    confirmVariant: 'primary'
})

const emit = defineEmits<Emits>()

// Watch for show changes to handle external state changes
watch(() => props.show, (newShow) => {
    if (!newShow) {
        handleClose()
    }
})

const handleClose = (): void => {
    emit('update:show', false)
}

const handleConfirm = (): void => {
    emit('confirm')
}
</script>
