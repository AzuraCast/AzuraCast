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
            <best-and-worst-tab :api-url="bestAndWorstUrl" :date-range="dateRange">
            </best-and-worst-tab>

            <listeners-by-time-period-tab :api-url="listenersByTimePeriodUrl" :date-range="dateRange">
            </listeners-by-time-period-tab>

            <browsers-tab v-if="showFullAnalytics" :api-url="byBrowserUrl" :date-range="dateRange">
            </browsers-tab>

            <countries-tab v-if="showFullAnalytics" :api-url="byCountryUrl" :date-range="dateRange">
            </countries-tab>
        </b-tabs>
    </section>
</template>

<script>
import {DateTime} from "luxon";
import DateRangeDropdown from "~/components/Common/DateRangeDropdown";
import ListenersByTimePeriodTab from "./Overview/ListenersByTimePeriodTab";
import BestAndWorstTab from "./Overview/BestAndWorstTab";
import BrowsersTab from "./Overview/BrowsersTab";
import CountriesTab from "~/components/Stations/Reports/Overview/CountriesTab";

export default {
    components: {
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
        byBrowserUrl: String,
        byCountryUrl: String,
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
