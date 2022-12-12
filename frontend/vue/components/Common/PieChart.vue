<template>
    <canvas ref="canvas">
        <slot></slot>
    </canvas>
</template>

<script setup>
import {get, templateRef, watchOnce} from "@vueuse/core";
import {Tableau20} from "~/vendor/chartjs-colorschemes/colorschemes.tableau";
import {Chart} from "chart.js";
import {onUnmounted} from "vue";

const props = defineProps({
    options: Object,
    data: Array,
    labels: Array,
    aspectRatio: {
        type: Number,
        default: 2
    }
});

const $canvas = templateRef('canvas');
let $chart = null;

watchOnce($canvas, () => {
    const defaultOptions = {
        type: 'pie',
        data: {
            labels: props.labels,
            datasets: props.data
        },
        options: {
            aspectRatio: props.aspectRatio,
            plugins: {
                colorschemes: {
                    scheme: Tableau20
                }
            }
        }
    };

    if ($chart) {
        $chart.destroy();
    }

    let chartOptions = _.defaultsDeep({}, props.options, defaultOptions);
    $chart = new Chart(get($canvas).getContext('2d'), chartOptions);
});

onUnmounted(() => {
    if ($chart) {
        $chart.destroy();
    }
});

</script>
