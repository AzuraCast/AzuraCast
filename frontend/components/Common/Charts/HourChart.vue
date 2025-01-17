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
import {useTranslate} from "~/vendor/gettext";
import {ref} from "vue";
import ChartAltValues from "~/components/Common/Charts/ChartAltValues.vue";
import useChart, {ChartProps, ChartTemplateRef} from "~/functions/useChart";

const props = defineProps<ChartProps>();

const $canvas = ref<ChartTemplateRef>(null);
const {$gettext} = useTranslate();

useChart<'bar'>(
    props,
    $canvas,
    {
        type: 'bar',
        options: {
            aspectRatio: props.aspectRatio,
            scales: {
                x: {
                    title: {
                        display: true,
                        text: $gettext('Hour')
                    }
                },
                y: {
                    title: {
                        display: true,
                        text: $gettext('Listeners')
                    },
                    min: 0
                }
            }
        }
    }
);
</script>
