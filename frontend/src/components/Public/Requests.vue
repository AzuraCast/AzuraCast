<template>
    <div class="container-fluid">
        <requests-data-table v-bind="props" />
    </div>
</template>
<script setup lang="ts">
import RequestsDataTable from "~/components/Public/Requests/RequestsDataTable.vue";
import requestsProps from "~/components/Public/Requests/requestsProps.ts";

const props = defineProps(requestsProps);
</script>
