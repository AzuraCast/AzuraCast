<template>
    <canvas ref="canvas">
        <slot></slot>
    </canvas>
</template>

<script>
import _ from 'lodash';

export default {
    name: 'DayOfWeekChart',
    inheritAttrs: true,
    props: {
        options: Object,
        data: Array,
        labels: Array
    },
    data () {
        return {
            _chart: null
        };
    },
    mounted () {
        Chart.platform.disableCSSInjection = true;

        this.renderChart();
    },
    methods: {
        renderChart () {
            const defaultOptions = {
                type: 'pie',
                data: {
                    labels: this.labels,
                    datasets: this.data
                },
                options: {
                    aspectRatio: 4,
                    plugins: {
                        colorschemes: {
                            scheme: 'tableau.Tableau20'
                        }
                    }
                }
            };

            if (this._chart)
                this._chart.destroy();

            let chartOptions = _.defaultsDeep(_.clone(this.options), defaultOptions);
            this._chart = new Chart(this.$refs.canvas.getContext('2d'), chartOptions);
        }
    },
    beforeDestroy () {
        if (this._chart) {
            this._chart.destroy();
        }
    }
};
</script>
