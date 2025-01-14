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
                        :options="{
                            enableTimePicker: true,
                            timezone: timezone
                        }"
                        class="btn-dark"
                    />
                </div>
            </div>
        </div>

        <div class="card-body">
            <tabs destroy-on-hide>
                <tab :label="$gettext('Best & Worst')">
                    <best-and-worst-tab
                        :api-url="bestAndWorstUrl"
                        :date-range="dateRange"
                    />
                </tab>

                <tab :label="$gettext('Listeners By Time Period')">
                    <listeners-by-time-period-tab
                        :api-url="listenersByTimePeriodUrl"
                        :date-range="dateRange"
                    />
                </tab>

                <tab :label="$gettext('Listening Time')">
                    <listening-time-tab
                        :api-url="listeningTimeUrl"
                        :date-range="dateRange"
                    />
                </tab>

                <tab :label="$gettext('Streams')">
                    <streams-tab
                        :api-url="byStreamUrl"
                        :date-range="dateRange"
                    />
                </tab>

                <tab
                    v-if="showFullAnalytics"
                    :label="$gettext('Clients')"
                >
                    <clients-tab
                        :api-url="byClientUrl"
                        :date-range="dateRange"
                    />
                </tab>

                <tab
                    v-if="showFullAnalytics"
                    :label="$gettext('Browsers')"
                >
                    <browsers-tab
                        :api-url="byBrowserUrl"
                        :date-range="dateRange"
                    />
                </tab>

                <tab
                    v-if="showFullAnalytics"
                    :label="$gettext('Countries')"
                >
                    <countries-tab
                        :api-url="byCountryUrl"
                        :date-range="dateRange"
                    />
                </tab>
            </tabs>
        </div>
    </section>
</template>

<script setup lang="ts">
import DateRangeDropdown from "~/components/Common/DateRangeDropdown.vue";
import ListenersByTimePeriodTab from "./Overview/ListenersByTimePeriodTab.vue";
import BestAndWorstTab from "./Overview/BestAndWorstTab.vue";
import BrowsersTab from "./Overview/BrowsersTab.vue";
import CountriesTab from "./Overview/CountriesTab.vue";
import StreamsTab from "./Overview/StreamsTab.vue";
import ClientsTab from "./Overview/ClientsTab.vue";
import ListeningTimeTab from "~/components/Stations/Reports/Overview/ListeningTimeTab.vue";
import {ref} from "vue";
import {getStationApiUrl} from "~/router";
import Tabs from "~/components/Common/Tabs.vue";
import Tab from "~/components/Common/Tab.vue";
import useStationDateTimeFormatter from "~/functions/useStationDateTimeFormatter.ts";
import {useAzuraCastStation} from "~/vendor/azuracast.ts";

const props = defineProps<{
    showFullAnalytics: boolean
}>();

const listenersByTimePeriodUrl = getStationApiUrl('/reports/overview/charts');
const bestAndWorstUrl = getStationApiUrl('/reports/overview/best-and-worst');
const byStreamUrl = getStationApiUrl('/reports/overview/by-stream');
const byBrowserUrl = getStationApiUrl('/reports/overview/by-browser');
const byCountryUrl = getStationApiUrl('/reports/overview/by-country');
const byClientUrl = getStationApiUrl('/reports/overview/by-client');
const listeningTimeUrl = getStationApiUrl('/reports/overview/by-listening-time');

const {timezone} = useAzuraCastStation();

const {now} = useStationDateTimeFormatter();

const nowTz = now();
const dateRange = ref({
    startDate: nowTz.minus({days: 13}).toJSDate(),
    endDate: nowTz.toJSDate(),
});
</script>
