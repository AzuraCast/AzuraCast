import {Chart, registerables} from "chart.js";
import {defaultsDeep} from "lodash";
import {computed, onMounted, onUnmounted, toRef, watch} from "vue";
import colorSchemesPlugin from '~/vendor/chartjs-colorschemes/plugin.colorschemes.js';
import zoomPlugin from 'chartjs-plugin-zoom';

import 'chartjs-adapter-luxon';
import '~/vendor/luxon';

Chart.register(...registerables);

Chart.register(colorSchemesPlugin);

Chart.register(zoomPlugin);

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
};

export default function useChart(
    props,
    $canvas,
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
