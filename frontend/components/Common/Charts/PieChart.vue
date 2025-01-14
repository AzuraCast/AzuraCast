<template>
    <canvas ref="$canvas">
        <slot>
            <chart-alt-values
                v-if="alt.length > 0"
                :alt="alt"
            />
        </slot>
    </canvas>
</template>

<script setup lang="ts">
import {ref} from "vue";
import ChartAltValues from "~/components/Common/Charts/ChartAltValues.vue";
import useChart, {ChartProps, ChartTemplateRef} from "~/functions/useChart";

const props = defineProps<ChartProps>();

const $canvas = ref<ChartTemplateRef>(null);

useChart<'pie'>(
    props,
    $canvas,
    {
        type: 'pie',
        options: {
            aspectRatio: props.aspectRatio,
        }
    }
);
</script>
