<template>
    <canvas ref="canvas">
        <slot></slot>
    </canvas>
</template>

<script>
import _ from 'lodash';
import {Chart} from 'chart.js';
import {Tableau20} from '~/vendor/chartjs-colorschemes/colorschemes.tableau.js';

export default {
    name: 'PieChart',
    inheritAttrs: true,
    props: {
        options: Object,
        data: Array,
        labels: Array,
        aspectRatio: {
            type: Number,
            default: 2
        }
    },
    data() {
        return {
            _chart: null
        };
    },
    mounted() {
        this.renderChart();
    },
    methods: {
        renderChart() {
            const defaultOptions = {
                type: 'pie',
                data: {
                    labels: this.labels,
                    datasets: this.data
                },
                options: {
                    aspectRatio: this.aspectRatio,
                    plugins: {
                        colorschemes: {
                            scheme: Tableau20
                        }
                    }
                }
            };

            if (this._chart)
                this._chart.destroy();

            let chartOptions = _.defaultsDeep({}, this.options, defaultOptions);
            this._chart = new Chart(this.$refs.canvas.getContext('2d'), chartOptions);
        }
    },
    beforeDestroy() {
        if (this._chart) {
            this._chart.destroy();
        }
    }
};
</script>
