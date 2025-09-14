<template>
    <div class="table-responsive">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th
                        v-for="key in fields"
                        :key="`${key}-header`"
                    >
                        {{ key }}
                    </th>
                </tr>
            </thead>
            <tbody>
                <tr
                    v-for="(row, index) in data"
                    :key="index"
                >
                    <td
                        v-for="key in fields"
                        :key="`row-${index}-${key}`"
                    >
                        {{ get(row, key, null) }}
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>

<script setup lang="ts">
import {get, omit} from "es-toolkit/compat";
import {computed} from "vue";
import {
    ApiAdminServerStatsNetworkInterfaceReceived,
    ApiAdminServerStatsNetworkInterfaceTransmitted
} from "~/entities/ApiInterfaces.ts";

type StatsSection = ApiAdminServerStatsNetworkInterfaceReceived
    | ApiAdminServerStatsNetworkInterfaceTransmitted

const props = defineProps<{
    row: StatsSection,
}>();

const visibleData = computed(() => {
    return {
        'speed': props.row.speed_readable,
        ...omit(props.row, 'speed_readable', 'speed_bytes')
    };
});

const fields = computed<string[]>(() => Object.keys(visibleData.value));

const data = computed(() => {
    return [
        visibleData.value
    ];
});
</script>
