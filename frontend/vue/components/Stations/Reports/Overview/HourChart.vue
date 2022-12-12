<template>
    <canvas ref="canvas">
        <slot></slot>
    </canvas>
</template>

<script setup>
import {get, templateRef, watchOnce} from "@vueuse/core";
import {Tableau20} from "~/vendor/chartjs-colorschemes/colorschemes.tableau";
import {Chart} from "chart.js";
import gettext from "~/vendor/gettext";
import {onUnmounted} from "vue";

const props = defineProps({
    options: Object,
    data: Array,
    labels: Array
});

let $chart = null;
const $canvas = templateRef('canvas');
const {$gettext} = gettext;

watchOnce($canvas, () => {
    const defaultOptions = {
        type: 'bar',
        data: {
            labels: props.labels,
            datasets: props.data
        },
        options: {
            aspectRatio: 2,
            plugins: {
                colorschemes: {
                    scheme: Tableau20
                }
            },
            scales: {
                x: {
                    scaleLabel: {
                        display: true,
                        labelString: $gettext('Hour')
                    }
                },
                y: {
                    scaleLabel: {
                        display: true,
                        labelString: $gettext('Listeners')
                    },
                    ticks: {
                        min: 0
                    }
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
