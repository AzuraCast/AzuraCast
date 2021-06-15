<template>
    <canvas ref="canvas">
        <slot></slot>
    </canvas>
</template>

<script>

export default {
    name: 'HourChart',
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
                type: 'bar',
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
                    },
                    scales: {
                        xAxes: [
                            {
                                scaleLabel: {
                                    display: true,
                                    labelString: this.$gettext('Hour')
                                }
                            }
                        ],
                        yAxes: [
                            {
                                scaleLabel: {
                                    display: true,
                                    labelString: this.$gettext('Listeners')
                                },
                                ticks: {
                                    min: 0
                                }
                            }
                        ]
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
