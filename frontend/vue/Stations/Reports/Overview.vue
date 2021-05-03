<template>
    <div id="reports-overview">
        <section class="card mb-4" role="region">
            <b-overlay variant="card" :show="chartsLoading">
                <div class="card-body py-5" v-if="chartsLoading">
                    &nbsp;
                </div>
                <b-tabs pills card lazy v-else>
                    <b-tab :title="langListenersByDay" active>
                        <time-series-chart style="width: 100%;" :data="chartsData.daily.metrics">
                            <span v-html="chartsData.daily.alt"></span>
                        </time-series-chart>
                    </b-tab>
                    <b-tab :title="langListenersByDayOfWeek">
                        <day-of-week-chart style="width: 100%;" :data="chartsData.day_of_week.metrics" :labels="chartsData.day_of_week.labels">
                            <span v-html="chartsData.day_of_week.alt"></span>
                        </day-of-week-chart>
                    </b-tab>
                    <b-tab :title="langListenersByHour">
                        <hour-chart style="width: 100%;" :data="chartsData.hourly.metrics" :labels="chartsData.hourly.labels">
                            <span v-html="chartsData.hourly.alt"></span>
                        </hour-chart>
                    </b-tab>
                </b-tabs>
            </b-overlay>
        </section>

        <div class="row">
            <div class="col-sm-6">
                <section class="card mb-3" role="region">
                    <div class="card-header bg-primary-dark">
                        <h3 class="card-title">
                            <translate key="reports_overview_best_songs">Best Performing Songs</translate>
                            <small>
                                <translate key="reports_overview_timeframe">in the last 48 hours</translate>
                            </small>
                        </h3>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-striped table-condensed table-nopadding">
                            <colgroup>
                                <col width="20%">
                                <col width="80%">
                            </colgroup>
                            <thead>
                            <tr>
                                <th>
                                    <translate key="reports_overview_col_change">Change</translate>
                                </th>
                                <th>
                                    <translate key="reports_overview_col_song">Song</translate>
                                </th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr v-for="row in bestAndWorst.best">
                                <td class="text-center text-success">
                                    <icon icon="keyboard_arrow_up"></icon>
                                    {{ row.stat_delta }}
                                    <br>
                                    <small>{{ row.stat_start }} to {{ row.stat_end }}</small>
                                </td>
                                <td>
                                    <span v-html="getSongText(row.song)"></span>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
            <div class="col-sm-6">
                <section class="card mb-3" role="region">
                    <div class="card-header bg-primary-dark">
                        <h3 class="card-title">
                            <translate key="reports_overview_worst_songs">Worst Performing Songs</translate>
                            <small>
                                <translate key="reports_overview_timeframe">in the last 48 hours</translate>
                            </small>
                        </h3>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-striped table-condensed table-nopadding">
                            <colgroup>
                                <col width="20%">
                                <col width="80%">
                            </colgroup>
                            <thead>
                            <tr>
                                <th>
                                    <translate key="reports_overview_col_change">Change</translate>
                                </th>
                                <th>
                                    <translate key="reports_overview_col_song">Song</translate>
                                </th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr v-for="row in bestAndWorst.best">
                                <td class="text-center text-danger">
                                    <icon icon="keyboard_arrow_down"></icon>
                                    {{ row.stat_delta }}
                                    <br>
                                    <small>{{ row.stat_start }} to {{ row.stat_end }}</small>
                                </td>
                                <td>
                                    <span v-html="getSongText(row.song)"></span>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </div>

        <div class="row">
            <div class="col-sm-12">
                <section class="card" role="region">
                    <div class="card-header bg-primary-dark">
                        <h3 class="card-title">
                            <translate key="reports_overview_most_played">Most Played Songs</translate>
                            <small>
                                <translate key="reports_overview_most_played_timeframe">in the last month</translate>
                            </small>
                        </h3>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-striped table-condensed table-nopadding">
                            <colgroup>
                                <col width="10%">
                                <col width="90%">
                            </colgroup>
                            <thead>
                            <tr>
                                <th>
                                    <translate key="reports_overview_col_plays">Plays</translate>
                                </th>
                                <th>
                                    <translate key="reports_overview_col_song">Song</translate>
                                </th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr v-for="row in mostPlayed">
                                <td class="text-center">
                                    {{ row.num_plays }}
                                </td>
                                <td>
                                    <span v-html="getSongText(row.song)"></span>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </div>


    </div>
</template>

<script>
import TimeSeriesChart from '../../Common/TimeSeriesChart';
import DataTable from '../../Common/DataTable';
import axios from 'axios';
import Icon from '../../Common/Icon';
import Avatar, { avatarProps } from '../../Common/Avatar';
import DayOfWeekChart from './DayOfWeekChart';
import HourChart from './HourChart';

export default {
    components: { HourChart, DayOfWeekChart, Avatar, Icon, DataTable, TimeSeriesChart },
    mixins: [avatarProps],
    props: {
        chartsUrl: String,
        bestAndWorstUrl: String,
        mostPlayedUrl: String
    },
    data () {
        return {
            chartsLoading: true,
            chartsData: {
                daily: {
                    metrics: [],
                    alt: ''
                },
                day_of_week: {
                    labels: [],
                    metrics: [],
                    alt: ''
                },
                hourly: {
                    labels: [],
                    metrics: [],
                    alt: ''
                }
            },
            bestAndWorstLoading: true,
            bestAndWorst: {
                best: [],
                worst: []
            },
            mostPlayedLoading: true,
            mostPlayed: []
        };
    },
    computed: {
        langListenersByDay () {
            return this.$gettext('Listeners by Day');
        },
        langListenersByDayOfWeek () {
            return this.$gettext('Listeners by Day of Week');
        },
        langListenersByHour () {
            return this.$gettext('Listeners by Hour');
        }
    },
    created () {
        moment.tz.setDefault('UTC');

        axios.get(this.chartsUrl).then((response) => {
            this.chartsData = response.data;
            this.chartsLoading = false;
        }).catch((error) => {
            console.error(error);
        });

        axios.get(this.bestAndWorstUrl).then((response) => {
            this.bestAndWorst = response.data;
            this.bestAndWorstLoading = false;
        }).catch((error) => {
            console.error(error);
        });

        axios.get(this.mostPlayedUrl).then((response) => {
            this.mostPlayed = response.data;
            this.mostPlayedLoading = false;
        }).catch((error) => {
            console.error(error);
        });
    },
    methods: {
        getSongText (song) {
            if (song.title !== '') {
                return '<b>' + song.title + '</b><br>' + song.artist;
            }

            return song.text;
        }
    }
};
</script>
