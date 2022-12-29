<template>
    <section
        class="card mb-4"
        role="region"
    >
        <div class="card-header bg-primary-dark">
            <div class="d-flex align-items-center">
                <h2 class="card-title flex-fill my-0">
                    {{ $gettext('Station Statistics') }}
                </h2>
                <div class="flex-shrink">
                    <date-range-dropdown
                        v-model="dateRange"
                        time-picker
                        :tz="stationTimeZone"
                    />
                </div>
            </div>
        </div>

        <b-tabs
            pills
            lazy
            nav-class="card-header-pills"
            nav-wrapper-class="card-header"
        >
            <b-tab>
                <template #title>
                    {{ $gettext('Best & Worst') }}
                </template>

                <best-and-worst-tab
                    :api-url="bestAndWorstUrl"
                    :date-range="dateRange"
                />
            </b-tab>

            <b-tab>
                <template #title>
                    {{ $gettext('Listeners By Time Period') }}
                </template>

                <listeners-by-time-period-tab
                    :api-url="listenersByTimePeriodUrl"
                    :date-range="dateRange"
                />
            </b-tab>

            <b-tab>
                <template #title>
                    {{ $gettext('Listening Time') }}
                </template>

                <listening-time-tab
                    :api-url="listeningTimeUrl"
                    :date-range="dateRange"
                />
            </b-tab>

            <b-tab>
                <template #title>
                    {{ $gettext('Streams') }}
                </template>

                <streams-tab
                    :api-url="byStreamUrl"
                    :date-range="dateRange"
                />
            </b-tab>

            <b-tab v-if="showFullAnalytics">
                <template #title>
                    {{ $gettext('Clients') }}
                </template>

                <clients-tab
                    :api-url="byClientUrl"
                    :date-range="dateRange"
                />
            </b-tab>

            <b-tab v-if="showFullAnalytics">
                <template #title>
                    {{ $gettext('Browsers') }}
                </template>

                <browsers-tab
                    :api-url="byBrowserUrl"
                    :date-range="dateRange"
                />
            </b-tab>

            <b-tab v-if="showFullAnalytics">
                <template #title>
                    {{ $gettext('Countries') }}
                </template>

                <countries-tab
                    :api-url="byCountryUrl"
                    :date-range="dateRange"
                />
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
