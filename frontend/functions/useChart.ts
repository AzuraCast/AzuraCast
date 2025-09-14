import {
    Chart,
    ChartConfiguration,
    ChartConfigurationCustomTypesPerDataset,
    ChartDataset,
    ChartType,
    DefaultDataPoint,
    registerables
} from "chart.js";
import {defaultsDeep} from "es-toolkit/compat";
import {computed, MaybeRefOrGetter, onMounted, onUnmounted, Ref, toRaw, toValue, watch} from "vue";
import zoomPlugin from "chartjs-plugin-zoom";
import chartjsColorSchemes from "~/vendor/chartjs_colorschemes.ts";

import "chartjs-adapter-luxon";
import "~/vendor/luxon";

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
    options?: Partial<ChartConfiguration<TType, TData, TLabel> | ChartConfigurationCustomTypesPerDataset<TType, TData, TLabel>>,
    data?: ChartDataset<TType, TData>[],
    aspectRatio?: number,
    alt?: ChartAltData[],
    labels?: Array<any>
}

export type ChartTemplateRef = HTMLCanvasElement | null;

export default function useChart<
    TType extends ChartType = ChartType,
    TData = DefaultDataPoint<TType>,
    TLabel = unknown
>(
    props: ChartProps,
    $canvas: Ref<ChartTemplateRef>,
    defaultOptions: MaybeRefOrGetter<
        Partial<ChartConfiguration<TType, TData, TLabel> | ChartConfigurationCustomTypesPerDataset<TType, TData, TLabel>>
    >
): {
    $chart: Chart<TType, TData, TLabel> | null
} {
    let $chart: Chart<TType, TData, TLabel> | null = null;

    const chartConfig = computed(() => {
        return defaultsDeep({
            options: {
                aspectRatio: props.aspectRatio ?? 2,
            },
            data: {
                datasets: toRaw(props.data),
                labels: toRaw(props.labels)
            }
        }, toValue(defaultOptions), props.options);
    });

    const rebuildChart = () => {
        $chart?.destroy();

        const chartContext = $canvas.value?.getContext('2d');
        if (!chartContext) {
            throw new Error("Cannot find chart context!");
        }

        $chart = new Chart(
            chartContext,
            chartConfig.value
        );
    }

    onMounted(rebuildChart);

    watch(
        () => chartConfig,
        () => {
            rebuildChart();
        },
        {deep: true}
    );

    onUnmounted(() => {
        $chart?.destroy();
    });

    return {
        $chart,
    }
}
