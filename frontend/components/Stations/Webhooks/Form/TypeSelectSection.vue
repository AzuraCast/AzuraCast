<template>
    <section class="card mb-3">
        <div class="card-header text-bg-primary">
            <h3 class="card-subtitle">
                {{ title }}
            </h3>
        </div>

        <div class="list-group list-group-flush">
            <a
                v-for="(type, key) in types"
                :key="key"
                class="list-group-item list-group-item-action"
                href="#"
                @click.prevent="selectType(key)"
            >
                <h6 class="font-weight-bold mb-0">
                    {{ type.title }}
                </h6>
                <p class="card-text small">
                    {{ type.description }}
                </p>
            </a>
        </div>
    </section>
</template>

<script setup lang="ts">
import {WebhookType, WebhookTypeDetails} from "~/entities/Webhooks.ts";

const props = defineProps<{
    title: string,
    types: Partial<WebhookTypeDetails>
}>();

const emit = defineEmits<{
    (e: 'select', type: WebhookType): void
}>();

const selectType = (type: WebhookType) => {
    emit('select', type);
}
</script>
