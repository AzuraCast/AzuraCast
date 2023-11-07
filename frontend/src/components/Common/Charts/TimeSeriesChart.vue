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
import {useTranslate} from "~/vendor/gettext";
import ChartAltValues from "~/components/Common/Charts/ChartAltValues.vue";
import useChart, {chartProps, ChartTemplateRef} from "~/functions/useChart";
import {useLuxon} from "~/vendor/luxon";

const props = defineProps({
    ...chartProps,
});

const $canvas = ref<ChartTemplateRef>(null);

const {$gettext} = useTranslate();
const {DateTime} = useLuxon();

useChart(
    props,
    $canvas,
    {
        type: 'line',
        options: {
            aspectRatio: props.aspectRatio,
            datasets: {
                line: {
                    spanGaps: true,
                    showLine: true
                }
            },
            plugins: {
                zoom: {
                    // Container for pan options
                    pan: {
                        enabled: true,
                        mode: 'x'
                    }
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
    }
);
</script>
