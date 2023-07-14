<template>
    <div
        class="toast align-items-center toast-notification mb-3"
        :class="'text-bg-'+variant"
        role="alert"
        aria-live="assertive"
        aria-atomic="true"
    >
        <div class="d-flex">
            <div class="toast-body">
                {{ message }}
            </div>
            <button
                type="button"
                class="btn-close me-2 m-auto"
                data-bs-dismiss="toast"
                aria-label="Close"
            />
        </div>
    </div>
</template>

<script setup>
const props = defineProps({
    message: {
        type: String,
        required: true
    },
    variant: {
        type: String,
        default: 'info'
    }
})
</script>
