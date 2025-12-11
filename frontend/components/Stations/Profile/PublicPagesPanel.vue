<template>
    <card-page header-id="hdr_public_pages">
        <template #header="{id}">
            <div class="d-flex align-items-center">
                <h3
                    :id="id"
                    class="card-title flex-fill my-0"
                >
                    {{ $gettext('Public Pages') }}
                </h3>
                <div class="flex-shrink-0">
                    <enabled-badge :enabled="stationData.enablePublicPages"/>
                </div>
            </div>
        </template>

        <template v-if="stationData.enablePublicPages">
            <table class="table table-striped table-responsive-md mb-0">
                <colgroup>
                    <col style="width: 30%;">
                    <col style="width: 70%;">
                </colgroup>
                <tbody>
                    <tr>
                        <td>{{ $gettext('Public Page') }}</td>
                        <td>
                            <a
                                :href="stationData.publicPageUrl"
                                target="_blank"
                            >{{ stationData.publicPageUrl }}</a>
                        </td>
                    </tr>
                    <tr v-if="stationData.enableOnDemand">
                        <td>{{ $gettext('On-Demand Media') }}</td>
                        <td>
                            <a
                                :href="stationData.onDemandUrl"
                                target="_blank"
                            >{{ stationData.onDemandUrl }}</a>
                        </td>
                    </tr>
                    <tr>
                        <td>{{ $gettext('Podcasts') }}</td>
                        <td>
                            <a
                                :href="stationData.publicPodcastsUrl"
                                target="_blank"
                            >{{ stationData.publicPodcastsUrl }}</a>
                        </td>
                    </tr>
                    <tr>
                        <td>{{ $gettext('Schedule') }}</td>
                        <td>
                            <a
                                :href="stationData.publicScheduleUrl"
                                target="_blank"
                            >{{ stationData.publicScheduleUrl }}</a>
                        </td>
                    </tr>
                </tbody>
            </table>
        </template>

        <template #footer_actions>
            <template v-if="stationData.enablePublicPages">
                <a
                    class="btn btn-link text-secondary"
                    @click.prevent="doOpenEmbed"
                >
                    <icon-ic-code/>

                    <span>
                        {{ $gettext('Embed Widgets') }}
                    </span>
                </a>
                <router-link
                    v-if="userAllowedForStation(StationPermissions.Profile)"
                    class="btn btn-link text-secondary"
                    :to="{name: 'stations:branding'}"
                >
                    <icon-ic-design-services/>

                    <span>
                        {{ $gettext('Edit Branding') }}
                    </span>
                </router-link>
                <button
                    v-if="userAllowedForStation(StationPermissions.Profile)"
                    type="button"
                    class="btn btn-link text-danger"
                    @click="togglePublicPages"
                >
                    <icon-ic-close/>

                    <span>
                        {{ $gettext('Disable') }}
                    </span>
                </button>
            </template>
            <template v-else>
                <button
                    v-if="userAllowedForStation(StationPermissions.Profile)"
                    type="button"
                    class="btn btn-link text-success"
                    @click="togglePublicPages"
                >
                    <icon-ic-check/>

                    <span>
                        {{ $gettext('Enable') }}
                    </span>
                </button>
            </template>
        </template>
    </card-page>

    <widget-modal ref="$widgetModal"/>
</template>

<script setup lang="ts">
import EnabledBadge from "~/components/Common/Badges/EnabledBadge.vue";
import {computed, useTemplateRef} from "vue";
import WidgetModal from "~/components/Stations/Profile/WidgetModal.vue";
import CardPage from "~/components/Common/CardPage.vue";
import {useUserAllowedForStation} from "~/functions/useUserallowedForStation.ts";
import useToggleFeature from "~/components/Stations/Profile/useToggleFeature";
import {StationPermissions} from "~/entities/ApiInterfaces.ts";
import {useStationData} from "~/functions/useStationQuery.ts";
import IconIcDesignServices from "~icons/ic/baseline-design-services";
import IconIcCheck from "~icons/ic/baseline-check";
import IconIcClose from "~icons/ic/baseline-close";
import IconIcCode from "~icons/ic/baseline-code";

const stationData = useStationData();

const {userAllowedForStation} = useUserAllowedForStation();

const $widgetModal = useTemplateRef('$widgetModal');

const doOpenEmbed = () => {
    $widgetModal.value?.open();
};

const togglePublicPages = useToggleFeature(
    'enable_public_page',
    computed(() => stationData.value.enablePublicPages),
);
</script>
