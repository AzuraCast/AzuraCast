<template>
    <section class="card mb-3">
        <div class="card-header text-bg-primary">
            <h3 class="card-subtitle">
                {{ title }}
            </h3>
        </div>

        <div class="list-group list-group-flush">
            <template v-for="(type, key) in types" :key="key as string">
                <a
                    v-if="type"
                    class="list-group-item list-group-item-action"
                    href="#"
                    @click.prevent="selectType(key as ActiveWebhookTypes)"
                >
                    <h6 class="font-weight-bold mb-0">
                        {{ type.title }}
                    </h6>
                    <p class="card-text small">
                        {{ type.description }}
                    </p>
                </a>
            </template>
        </div>
    </section>
</template>

<script setup lang="ts">
import {ActiveWebhookTypes, WebhookTypeDetails} from "~/entities/Webhooks.ts";

defineProps<{
    title: string,
    types: Partial<WebhookTypeDetails>
}>();

const emit = defineEmits<{
    (e: 'select', type: ActiveWebhookTypes): void
}>();

const selectType = (type: ActiveWebhookTypes) => {
    emit('select', type);
}
</script>
