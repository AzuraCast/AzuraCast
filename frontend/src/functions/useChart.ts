import {Chart, registerables} from "chart.js";
import {defaultsDeep} from "lodash";
import {computed, onMounted, onUnmounted, Ref, toRef, watch} from "vue";
import zoomPlugin from 'chartjs-plugin-zoom';
import chartjsColorSchemes from "~/vendor/chartjs_colorschemes.ts";

import 'chartjs-adapter-luxon';
import '~/vendor/luxon';

Chart.register(...registerables);

Chart.register(zoomPlugin);

Chart.register(chartjsColorSchemes);

export const chartProps = {
    options: {
        type: Object,
        default: () => {
            return {};
        }
    },
    data: {
        type: Array,
        default: () => {
            return [];
        }
    },
    alt: {
        type: Array,
        default: () => {
            return [];
        }
    },
    aspectRatio: {
        type: Number,
        default: 2,
    }
};

export type ChartTemplateRef = HTMLCanvasElement | null;

export default function useChart(
    props,
    $canvas: Ref<ChartTemplateRef>,
    defaultOptions = {}
) {
    let $chart = null;

    const chartConfig = computed(() => {
        const config = defaultsDeep({
            data: {}
        }, props.options, defaultOptions);

        config.data.datasets = props.data;
        if (props.labels) {
            config.data.labels = props.labels;
        }

        return config;
    });

    const rebuildChart = () => {
        $chart?.destroy();

        $chart = new Chart(
            $canvas.value.getContext('2d'),
            chartConfig.value
        );
    }

    onMounted(rebuildChart);

    onUnmounted(() => {
        $chart?.destroy();
    });

    watch(toRef(props, 'options'), rebuildChart);

    watch(toRef(props, 'data'), (newData) => {
        if ($chart) {
            $chart.data.datasets = newData;
            $chart.update();
        }
    });

    if (props.labels) {
        watch(toRef(props, 'labels'), (newLabels) => {
            if ($chart) {
                $chart.data.labels = newLabels;
                $chart.update();
            }
        });
    }

    return {
        $chart,
    }
}
