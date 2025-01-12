<template>
    <div class="container-fluid">
        <requests-data-table v-bind="props" />
    </div>
</template>

<script lang="ts">
export interface RequestsProps {
    requestListUri: string,
    showAlbumArt?: boolean
    customFields?: Array<any>
}
</script>

<script setup lang="ts">
import RequestsDataTable from "~/components/Public/Requests/RequestsDataTable.vue";

defineOptions({
    inheritAttrs: false
});

const props = withDefaults(
    defineProps<RequestsProps>(),
    {
        showAlbumArt: true,
        customFields: () => []
    }
);
</script>
