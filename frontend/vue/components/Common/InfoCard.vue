<template>
    <div
        class="card-body alert-info d-flex align-items-center"
        role="alert"
        aria-live="off"
    >
        <div class="flex-shrink-0 mr-2">
            <icon icon="info" />
        </div>
        <div class="flex-fill">
            <slot />
        </div>
    </div>
</template>

<script setup>
import Icon from './Icon';</script>
