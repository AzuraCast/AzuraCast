<template>
    <div
        class="toast align-items-center toast-notification mb-3"
        :class="'text-bg-'+variant"
        role="alert"
        aria-live="assertive"
        aria-atomic="true"
    >
        <template v-if="title">
            <div
                v-if="title"
                class="toast-header"
            >
                <strong class="me-auto">
                    {{ title }}
                </strong>
                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="toast"
                    :aria-label="$gettext('Close')"
                />
            </div>
            <div class="toast-body">
                <slot>
                    {{ message }}
                </slot>
            </div>
        </template>
        <template v-else>
            <div class="d-flex">
                <div class="toast-body">
                    <slot>
                        {{ message }}
                    </slot>
                </div>
                <button
                    type="button"
                    class="btn-close me-2 m-auto"
                    data-bs-dismiss="toast"
                    :aria-label="$gettext('Close')"
                />
            </div>
        </template>
    </div>
</template>

<script setup lang="ts">
const props = defineProps({
    title: {
        type: String,
        default: null
    },
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
