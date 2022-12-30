<template>
    <canvas ref="$canvas">
        <slot />
    </canvas>
</template>

<script setup>
import {Tableau20} from "~/vendor/chartjs-colorschemes/colorschemes.tableau";
import {Chart} from "chart.js";
import {onMounted, onUnmounted, ref} from "vue";
import {defaultsDeep} from "lodash";

const props = defineProps({
    options: {
        type: Object,
        required: true
    },
    data: {
        type: Array,
        default: () => {
            return [];
        }
    },
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
let $chart = null;

onMounted(() => {
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

    let chartOptions = defaultsDeep({}, props.options, defaultOptions);
    $chart = new Chart($canvas.value.getContext('2d'), chartOptions);
});

onUnmounted(() => {
    if ($chart) {
        $chart.destroy();
    }
});

</script>
