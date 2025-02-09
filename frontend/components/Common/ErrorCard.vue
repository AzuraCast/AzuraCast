<template>
    <div
        class="card-body alert-danger d-flex align-items-center"
        role="alert"
    >
        <div class="flex-shrink-0 me-2">
            <icon :icon="IconError" />
        </div>
        <div class="flex-fill">
            <slot />
        </div>
    </div>
</template>

<script setup lang="ts">
import Icon from "~/components/Common/Icon.vue";
import {IconError} from "~/components/Common/icons";</script>
