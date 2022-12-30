<template>
    <canvas ref="$canvas">
        <slot />
    </canvas>
</template>

<script setup>
import {get} from "@vueuse/core";
import {Tableau20} from "~/vendor/chartjs-colorschemes/colorschemes.tableau";
import {DateTime} from "luxon";
import _ from "lodash";
import {Chart} from "chart.js";
import {onMounted, onUnmounted, ref} from "vue";
import {useTranslate} from "~/vendor/gettext";

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
    }
});

const $canvas = ref(); // Template ref
let $chart = null;

const {$gettext} = useTranslate();

onMounted(() => {
    const defaultOptions = {
        type: 'line',
        data: {
            datasets: props.data
        },
        options: {
            aspectRatio: 3,
            plugins: {
                zoom: {
                    // Container for pan options
                    pan: {
                        enabled: true,
                        mode: 'x'
                    }
                },
                colorschemes: {
                    scheme: Tableau20
                }
            },
            scales: {
                x: {
                    type: 'time',
                    distribution: 'linear',
                    display: true,
                    min: DateTime.now().minus({days: 30}).toJSDate(),
                    max: DateTime.now().toJSDate(),
                    time: {
                        unit: 'day',
                        tooltipFormat: DateTime.DATE_SHORT,
                    },
                    ticks: {
                        source: 'data',
                        autoSkip: true
                    }
                },
                y: {
                    display: true,
                    scaleLabel: {
                        display: true,
                        labelString: $gettext('Listeners')
                    },
                    ticks: {
                        min: 0
                    }
                }
            },
            tooltips: {
                intersect: false,
                mode: 'index',
                callbacks: {
                    label: function (tooltipItem, myData) {
                        let label = myData.datasets[tooltipItem.datasetIndex].label || '';
                        if (label) {
                            label += ': ';
                        }
                        label += parseFloat(tooltipItem.value).toFixed(2);
                        return label;
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
