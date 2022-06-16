<template>
    <canvas ref="canvas">
        <slot></slot>
    </canvas>
</template>

<script>
import {Chart} from 'chart.js';
import {Tableau20} from '~/vendor/chartjs-colorschemes/colorschemes.tableau.js';

export default {
    name: 'HourChart',
    inheritAttrs: true,
    props: {
        options: Object,
        data: Array,
        labels: Array
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
                type: 'bar',
                data: {
                    labels: this.labels,
                    datasets: this.data
                },
                options: {
                    aspectRatio: 2,
                    plugins: {
                        colorschemes: {
                            scheme: Tableau20
                        }
                    },
                    scales: {
                        x: {
                            scaleLabel: {
                                display: true,
                                labelString: this.$gettext('Hour')
                            }
                        },
                        y: {
                            scaleLabel: {
                                display: true,
                                labelString: this.$gettext('Listeners')
                            },
                            ticks: {
                                min: 0
                            }
                        }
                    }
                }
            };

            if (this._chart) this._chart.destroy();

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
