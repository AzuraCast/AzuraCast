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

<script setup>
import {Tableau20} from "~/vendor/chartjs-colorschemes/colorschemes.tableau";
import {ref} from "vue";
import ChartAltValues from "~/components/Common/Charts/ChartAltValues.vue";
import useChart, {chartProps} from "~/functions/useChart";

const props = defineProps({
    ...chartProps,
    labels: {
        type: Array,
        default: () => {
            return [];
        }
    },
    aspectRatio: {
        type: Number,
        default: 2
    }
});

const $canvas = ref(); // Template ref

useChart(
    props,
    $canvas,
    {
        type: 'pie',
        options: {
            aspectRatio: props.aspectRatio,
            plugins: {
                colorschemes: {
                    scheme: Tableau20
                }
            }
        }
    }
);
</script>
