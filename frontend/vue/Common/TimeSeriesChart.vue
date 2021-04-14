<template>
    <canvas ref="canvas">
        <slot></slot>
    </canvas>
</template>

<script>
import _ from 'lodash';

export default {
    name: 'TimeSeriesChart',
    inheritAttrs: true,
    props: {
        options: Object,
        data: Array
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
                type: 'line',
                data: {
                    datasets: this.data
                },
                options: {
                    aspectRatio: 3,
                    plugins: {
                        zoom: {
                            // Container for pan options
                            pan: {
                                enabled: true,
                                mode: 'x'
                            }
                        },
                        colorschemes: {
                            scheme: 'tableau.Tableau20'
                        }
                    },
                    scales: {
                        xAxes: [{
                            type: 'time',
                            distribution: 'linear',
                            time: {
                                unit: 'day'
                            },
                            ticks: {
                                source: 'data',
                                autoSkip: true,
                                min: moment().subtract(30, 'days')
                            }
                        }],
                        yAxes: [{
                            scaleLabel: {
                                display: true,
                                labelString: this.$gettext('Listeners')
                            },
                            ticks: {
                                min: 0
                            }
                        }]
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
            };

            if (this._chart) this._chart.destroy();

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
