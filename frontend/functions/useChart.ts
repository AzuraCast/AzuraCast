import {Chart, registerables} from "chart.js";
import {defaultsDeep} from "lodash";
import {computed, isRef, MaybeRef, onMounted, onUnmounted, Ref, toRef, toValue, watch} from "vue";
import zoomPlugin from 'chartjs-plugin-zoom';
import chartjsColorSchemes from "~/vendor/chartjs_colorschemes.ts";

import 'chartjs-adapter-luxon';
import '~/vendor/luxon';
import {
    ChartConfiguration,
    ChartConfigurationCustomTypesPerDataset,
    ChartType,
    DefaultDataPoint
} from "chart.js/dist/types";

Chart.register(...registerables);

Chart.register(zoomPlugin);

Chart.register(chartjsColorSchemes);

interface ChartAltValue {
    label: string,
    type: string,
    original: string | number,
    value: string
}

export interface ChartAltData {
    label: string,
    values: ChartAltValue[]
}

export interface ChartProps<
    TType extends ChartType = ChartType,
    TData = DefaultDataPoint<TType>,
    TLabel = unknown
> {
    options?: ChartConfiguration<TType, TData, TLabel> | ChartConfigurationCustomTypesPerDataset<TType, TData, TLabel>,
    data?: any[],
    aspectRatio?: number,
    alt?: ChartAltData[],
    labels?: Array<any>
}

export const chartProps = {
};

export type ChartTemplateRef = HTMLCanvasElement | null;

export default function useChart<
    TType extends ChartType = ChartType,
    TData = DefaultDataPoint<TType>,
    TLabel = unknown
>(
    initialProps: ChartProps,
    $canvas: Ref<ChartTemplateRef>,
    defaultOptions: MaybeRef<
        ChartConfiguration<TType, TData, TLabel> | ChartConfigurationCustomTypesPerDataset<TType, TData, TLabel>
    >
): {
    $chart: Chart<TType, TData, TLabel> | null
} {
    const props: ChartProps = {
        options: {},
        data: [],
        alt: [],
        aspectRatio: 2,
        ...initialProps
    };

    let $chart = null;

    const chartConfig = computed(() => {
        const config = defaultsDeep({
            data: {}
        }, props.options, toValue(defaultOptions));

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

    if (isRef(defaultOptions)) {
        watch(defaultOptions, rebuildChart);
    }

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
