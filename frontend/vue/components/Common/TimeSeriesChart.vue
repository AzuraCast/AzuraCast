<template>
    <canvas ref="canvas">
        <slot></slot>
    </canvas>
</template>

<script>
import _ from 'lodash';
import {DateTime} from "luxon";
import {Chart} from 'chart.js';

import {Tableau20} from '~/vendor/chartjs-colorschemes/colorschemes.tableau.js';

export default {
    name: 'TimeSeriesChart',
    inheritAttrs: true,
    props: {
        options: Object,
        data: Array
    },
    data() {
        return {
            _chart: null
        };
    },
    mounted () {
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
                            scheme: Tableau20
                        }
                    },
                    scales: {
                        x: {
                            type: 'time',
                            distribution: 'linear',
                            display: true,
                            min: DateTime.now().minus({days: 30}).toJSDate(),
                            max: DateTime.now().toJSDate(),
                            time: {
                                unit: 'day',
                                tooltipFormat: DateTime.DATE_SHORT,
                            },
                            ticks: {
                                source: 'data',
                                autoSkip: true
                            }
                        },
                        y: {
                            display: true,
                            scaleLabel: {
                                display: true,
                                labelString: this.$gettext('Listeners')
                            },
                            ticks: {
                                min: 0
                            }
                        }
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

            let chartOptions = _.defaultsDeep({}, this.options, defaultOptions);
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
