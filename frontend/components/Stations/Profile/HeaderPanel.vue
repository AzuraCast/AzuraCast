<template>
    <div class="outside-card-header">
        <h2 class="display-6 m-0">
            {{ stationData.name }}<br>
            <small
                v-if="stationData.description"
                class="text-muted"
            >
                {{ stationData.description }}
            </small>
        </h2>
    </div>
</template>

<script setup lang="ts">
import {useStationData} from "~/functions/useStationQuery.ts";

const stationData = useStationData();
</script>
