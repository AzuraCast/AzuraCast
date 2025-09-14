<template>
    <canvas ref="$canvas">
        <slot>
            <chart-alt-values
                v-if="alt && alt.length > 0"
                :alt="alt"
            />
        </slot>
    </canvas>
</template>

<script setup lang="ts">
import {useTemplateRef} from "vue";
import ChartAltValues from "~/components/Common/Charts/ChartAltValues.vue";
import useChart, {ChartProps} from "~/functions/useChart";

const props = defineProps<ChartProps>();

const $canvas = useTemplateRef('$canvas');

useChart<'pie'>(
    props,
    $canvas,
    {
        type: 'pie'
    }
);
</script>
