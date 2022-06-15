<template>
    <section class="card mb-4" role="region">
        <div class="card-header bg-primary-dark">
            <div class="d-flex align-items-center">
                <h2 class="card-title flex-fill my-0">
                    <translate key="hdr">Station Statistics</translate>
                </h2>
                <div class="flex-shrink">
                    <date-range-dropdown time-picker v-model="dateRange" :tz="stationTimeZone"></date-range-dropdown>
                </div>
            </div>
        </div>

        <b-tabs pills lazy nav-class="card-header-pills" nav-wrapper-class="card-header">
            <b-tab>
                <template #title>
                    <translate key="tab_best_and_worst">Best & Worst</translate>
                </template>

                <best-and-worst-tab :api-url="bestAndWorstUrl" :date-range="dateRange">
                </best-and-worst-tab>
            </b-tab>

            <b-tab>
                <template #title>
                    <translate key="tab_by_time_period">Listeners By Time Period</translate>
                </template>

                <listeners-by-time-period-tab :api-url="listenersByTimePeriodUrl" :date-range="dateRange">
                </listeners-by-time-period-tab>
            </b-tab>

            <b-tab>
                <template #title>
                    <translate key="tab_listening_time">Listening Time</translate>
                </template>

                <listening-time-tab :api-url="listeningTimeUrl" :date-range="dateRange">
                </listening-time-tab>
            </b-tab>

            <b-tab>
                <template #title>
                    <translate key="tab_streams">Streams</translate>
                </template>

                <streams-tab :api-url="byStreamUrl" :date-range="dateRange">
                </streams-tab>
            </b-tab>

            <b-tab v-if="showFullAnalytics">
                <template #title>
                    <translate key="tab_clients">Clients</translate>
                </template>

                <clients-tab :api-url="byClientUrl" :date-range="dateRange">
                </clients-tab>
            </b-tab>

            <b-tab v-if="showFullAnalytics">
                <template #title>
                    <translate key="tab_browsers">Browsers</translate>
                </template>

                <browsers-tab :api-url="byBrowserUrl" :date-range="dateRange">
                </browsers-tab>
            </b-tab>

            <b-tab v-if="showFullAnalytics">
                <template #title>
                    <translate key="tab_countries">Countries</translate>
                </template>

                <countries-tab :api-url="byCountryUrl" :date-range="dateRange">
                </countries-tab>
            </b-tab>
        </b-tabs>
    </section>
</template>

<script>
import {DateTime} from "luxon";
import DateRangeDropdown from "~/components/Common/DateRangeDropdown";
import ListenersByTimePeriodTab from "./Overview/ListenersByTimePeriodTab";
import BestAndWorstTab from "./Overview/BestAndWorstTab";
import BrowsersTab from "./Overview/BrowsersTab";
import CountriesTab from "./Overview/CountriesTab";
import StreamsTab from "./Overview/StreamsTab";
import ClientsTab from "./Overview/ClientsTab";
import ListeningTimeTab from "~/components/Stations/Reports/Overview/ListeningTimeTab";

export default {
    components: {
        ListeningTimeTab,
        ClientsTab,
        StreamsTab,
        CountriesTab,
        BrowsersTab,
        BestAndWorstTab,
        ListenersByTimePeriodTab,
        DateRangeDropdown
    },
    props: {
        stationTimeZone: String,
        showFullAnalytics: Boolean,
        listenersByTimePeriodUrl: String,
        bestAndWorstUrl: String,
        byStreamUrl: String,
        byClientUrl: String,
        byBrowserUrl: String,
        byCountryUrl: String,
        listeningTimeUrl: String
    },
    data() {
        let nowTz = DateTime.now().setZone(this.stationTimeZone);

        return {
            dateRange: {
                startDate: nowTz.minus({days: 13}).toJSDate(),
                endDate: nowTz.toJSDate(),
            },
        };
    },
};
</script>
