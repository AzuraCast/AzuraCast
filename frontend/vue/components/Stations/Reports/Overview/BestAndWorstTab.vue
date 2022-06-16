<template>
    <b-overlay variant="card" :show="loading">
        <div class="card-body py-5" v-if="loading">
            &nbsp;
        </div>
        <div class="card-body" v-else>
            <b-row>
                <b-col md="6" class="mb-4">
                    <fieldset>
                        <legend>
                            <translate key="reports_overview_best_songs">Best Performing Songs</translate>
                        </legend>

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
                    </fieldset>
                </b-col>
                <b-col md="6" class="mb-4">
                    <fieldset>
                        <legend>
                            <translate key="reports_overview_worst_songs">Worst Performing Songs</translate>
                        </legend>

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
                            <tr v-for="row in bestAndWorst.worst">
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
                    </fieldset>
                </b-col>

                <b-col md="12" class="mb-4">
                    <fieldset>
                        <legend>
                            <translate key="reports_overview_most_played">Most Played Songs</translate>
                        </legend>

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
                    </fieldset>
                </b-col>
            </b-row>
        </div>
    </b-overlay>
</template>

<script>
import {DateTime} from "luxon";
import Icon from "~/components/Common/Icon";
import IsMounted from "~/components/Common/IsMounted";

export default {
    name: 'BestAndWorstTab',
    components: {Icon},
    mixins: [IsMounted],
    props: {
        dateRange: Object,
        apiUrl: String,
    },
    data() {
        return {
            loading: true,
            bestAndWorst: {
                best: [],
                worst: []
            },
            mostPlayed: [],
        };
    },
    watch: {
        dateRange() {
            if (this.isMounted) {
                this.relist();
            }
        }
    },
    mounted() {
        this.relist();
    },
    methods: {
        relist() {
            this.loading = true;
            this.axios.get(this.apiUrl, {
                params: {
                    start: DateTime.fromJSDate(this.dateRange.startDate).toISO(),
                    end: DateTime.fromJSDate(this.dateRange.endDate).toISO()
                }
            }).then((response) => {
                this.bestAndWorst = response.data.bestAndWorst;
                this.mostPlayed = response.data.mostPlayed;
                this.loading = false;
            });
        },
        getSongText(song) {
            if (song.title !== '') {
                return '<b>' + song.title + '</b><br>' + song.artist;
            }

            return song.text;
        }
    }
}
</script>
