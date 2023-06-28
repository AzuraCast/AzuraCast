<template>
    <div
        class="card-body alert-danger d-flex align-items-center"
        role="alert"
    >
        <div class="flex-shrink-0 me-2">
            <icon icon="error" />
        </div>
        <div class="flex-fill">
            <slot />
        </div>
    </div>
</template>

<script setup>
import Icon from './Icon';</script>
