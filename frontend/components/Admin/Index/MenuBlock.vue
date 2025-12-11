<template>
    <section v-for="panel in items"
             :key="panel.key"
             class="card mb-3">
        <div class="card-header text-bg-primary d-flex align-items-center">
            <div class="flex-fill">
                <h2 class="card-title">
                    {{ panel.label }}
                </h2>
            </div>
            <div class="flex-shrink-0 pt-1">
                <component
                    v-if="panel.icon"
                    :is="panel.icon()"
                    class="lg"
                />
            </div>
        </div>

        <div class="list-group list-group-flush">
            <template v-for="item in panel.items" :key="item.key">
                <router-link
                    v-if="item.url"
                    :to="item.url"
                    class="list-group-item list-group-item-action"
                >
                    {{ item.label }}
                </router-link>
            </template>
        </div>
    </section>
</template>

<script setup lang="ts">

import {MenuCategory} from "~/functions/filterMenu.ts";

defineProps<{
    items: MenuCategory[]
}>();
</script>
