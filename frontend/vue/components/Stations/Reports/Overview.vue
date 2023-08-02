<template>
    <section
        class="card mb-4"
        role="region"
    >
        <div class="card-header text-bg-primary">
            <div class="d-flex align-items-center">
                <h2 class="card-title flex-fill my-0">
                    {{ $gettext('Station Statistics') }}
                </h2>
                <div class="flex-shrink">
                    <date-range-dropdown
                        v-model="dateRange"
                        time-picker
                        :tz="timezone"
                    />
                </div>
            </div>
        </div>

        <div class="card-body">
            <o-tabs
                nav-tabs-class="nav-tabs"
                content-class="mt-3"
                destroy-on-hide
            >
                <o-tab-item :label="$gettext('Best & Worst')">
                    <best-and-worst-tab
                        :api-url="bestAndWorstUrl"
                        :date-range="dateRange"
                    />
                </o-tab-item>

                <o-tab-item :label="$gettext('Listeners By Time Period')">
                    <listeners-by-time-period-tab
                        :api-url="listenersByTimePeriodUrl"
                        :date-range="dateRange"
                    />
                </o-tab-item>

                <o-tab-item :label="$gettext('Listening Time')">
                    <listening-time-tab
                        :api-url="listeningTimeUrl"
                        :date-range="dateRange"
                    />
                </o-tab-item>

                <o-tab-item :label="$gettext('Streams')">
                    <streams-tab
                        :api-url="byStreamUrl"
                        :date-range="dateRange"
                    />
                </o-tab-item>

                <o-tab-item
                    v-if="showFullAnalytics"
                    :label="$gettext('Clients')"
                >
                    <clients-tab
                        :api-url="byClientUrl"
                        :date-range="dateRange"
                    />
                </o-tab-item>

                <o-tab-item
                    v-if="showFullAnalytics"
                    :label="$gettext('Browsers')"
                >
                    <browsers-tab
                        :api-url="byBrowserUrl"
                        :date-range="dateRange"
                    />
                </o-tab-item>

                <o-tab-item
                    v-if="showFullAnalytics"
                    :label="$gettext('Countries')"
                >
                    <countries-tab
                        :api-url="byCountryUrl"
                        :date-range="dateRange"
                    />
                </o-tab-item>
            </o-tabs>
        </div>
    </section>
</template>

<script setup>
import {DateTime} from "luxon";
import DateRangeDropdown from "~/components/Common/DateRangeDropdown";
import ListenersByTimePeriodTab from "./Overview/ListenersByTimePeriodTab";
import BestAndWorstTab from "./Overview/BestAndWorstTab";
import BrowsersTab from "./Overview/BrowsersTab";
import CountriesTab from "./Overview/CountriesTab";
import StreamsTab from "./Overview/StreamsTab";
import ClientsTab from "./Overview/ClientsTab";
import ListeningTimeTab from "~/components/Stations/Reports/Overview/ListeningTimeTab";
import {ref} from "vue";
import {useAzuraCastStation} from "~/vendor/azuracast";

const props = defineProps({
    showFullAnalytics: {
        type: Boolean,
        required: true
    },
    listenersByTimePeriodUrl: {
        type: String,
        required: true
    },
    bestAndWorstUrl: {
        type: String,
        required: true
    },
    byStreamUrl: {
        type: String,
        required: true
    },
    byClientUrl: {
        type: String,
        required: true
    },
    byBrowserUrl: {
        type: String,
        required: true
    },
    byCountryUrl: {
        type: String,
        required: true
    },
    listeningTimeUrl: {
        type: String,
        required: true
    }
});

const {timezone} = useAzuraCastStation();
const nowTz = DateTime.now().setZone(timezone);

const dateRange = ref({
    startDate: nowTz.minus({days: 13}).toJSDate(),
    endDate: nowTz.toJSDate(),
});
</script>
