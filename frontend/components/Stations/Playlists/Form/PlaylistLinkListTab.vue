<template>
    <tab
        :id="tabId"
        :label="label"
    >
        <form-markup
            v-if="items.length === 0"
            :id="emptyId"
        >
            <template #label>
                {{ emptyLabel }}
            </template>
            <p>
                {{ emptyText }}
            </p>
        </form-markup>
        <template v-else>
            <p>
                {{ description }}
            </p>
            <ul class="list-group list-group-flush border rounded">
                <li
                    v-for="item in items"
                    :key="item.id"
                    class="list-group-item p-0"
                >
                    <a
                        href="#"
                        class="playlist-link-item d-block w-100 p-3"
                        @click.prevent="emit('navigate', item)"
                    >
                        {{ item.name }}
                    </a>
                </li>
            </ul>
        </template>
    </tab>
</template>

<script setup lang="ts">
import Tab from "~/components/Common/Tab.vue";
import FormMarkup from "~/components/Form/FormMarkup.vue";
import { PlaylistBreadcrumb } from "~/entities/StationPlaylist.ts";

defineProps<{
    tabId?: string;
    label: string;
    description: string;
    emptyId: string;
    emptyLabel: string;
    emptyText: string;
    items: PlaylistBreadcrumb[];
}>();

const emit = defineEmits<(e: "navigate", item: PlaylistBreadcrumb) => void>();
</script>

<style lang="scss" scoped>
.playlist-link-item {
    cursor: pointer;

    &:hover {
        background-image: linear-gradient(to bottom, rgba(0, 0, 0, .12), rgba(0, 0, 0, .12));
    }
}
</style>
