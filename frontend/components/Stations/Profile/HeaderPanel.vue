<template>
    <div class="outside-card-header">
        <h1>
            {{ stationData.name }}
        </h1>
        <h3 class="fw-normal text-muted" v-if="stationData.description">
            {{ stationData.description }}
        </h3>
    </div>
</template>

<script setup lang="ts">
import {useStationData} from "~/functions/useStationQuery.ts";

const stationData = useStationData();
</script>
