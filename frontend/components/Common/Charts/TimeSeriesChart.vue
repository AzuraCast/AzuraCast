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
import {computed, ref} from "vue";
import {useTranslate} from "~/vendor/gettext";
import ChartAltValues from "~/components/Common/Charts/ChartAltValues.vue";
import useChart, {ChartProps, ChartTemplateRef} from "~/functions/useChart";
import {useLuxon} from "~/vendor/luxon";

interface TimeSeriesChartProps extends ChartProps<'line'> {
    tz?: string,
}

const props = withDefaults(
    defineProps<TimeSeriesChartProps>(),
    {
        tz: 'UTC'
    }
);

const $canvas = ref<ChartTemplateRef>(null);

const {$gettext} = useTranslate();
const {DateTime} = useLuxon();

useChart<'line'>(
    props,
    $canvas,
    computed(() => ({
        type: 'line',
        options: {
            aspectRatio: props.aspectRatio ?? 2,
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
                }, 
                tooltip: {
                    intersect: false,
                    mode: 'index',
                    callbacks: {
                        title: function (ctx) {
                            const title: string[] = [];

                            ctx.forEach((ctxRow) => {
                                title.push(
                                    DateTime.fromMillis(ctxRow.parsed.x).setZone(props.tz)?.toLocaleString(DateTime.DATE_SHORT)
                                );
                            });

                            return title.join(', ');
                        },
                        label: function (ctx) {
                            let label = ctx.dataset.label || '';
                            if (label) {
                                label += ': ';
                            }

                            label += ctx.parsed.y.toFixed(2);

                            return label;
                        }
                    }
                }
            },
            scales: {
                x: {
                    type: 'time',
                    display: true,
                    min: Number(DateTime.local({ zone: props.tz }).minus({ days: 30 }).toMillis()),
                    max: Number(DateTime.local({ zone: props.tz }).toMillis()),
                    adapters: {
                        date: {
                            setZone: true,
                            zone: props.tz
                        }
                    },
                    time: {
                        unit: 'day'
                    },
                    ticks: {
                        source: 'data',
                        autoSkip: true
                    }
                },
                y: {
                    display: true,
                    title: {
                        display: true,
                        text: $gettext('Listeners')
                    },
                    min: 0
                }
            }
        }
    }))
);
</script>
