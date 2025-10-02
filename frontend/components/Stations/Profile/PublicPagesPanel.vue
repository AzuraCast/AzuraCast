<template>
    <card-page header-id="hdr_public_pages">
        <template #header="{id}">
            <h3
                :id="id"
                class="card-title"
            >
                {{ $gettext('Public Pages') }}
                <enabled-badge :enabled="stationData.enablePublicPages"/>
            </h3>
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
                                :href="profileData.publicPageUri"
                                target="_blank"
                            >{{ profileData.publicPageUri }}</a>
                        </td>
                    </tr>
                    <tr v-if="stationData.features.streamers && stationData.enableStreamers">
                        <td>{{ $gettext('Web DJ') }}</td>
                        <td>
                            <a
                                :href="profileData.publicWebDjUri"
                                target="_blank"
                            >{{ profileData.publicWebDjUri }}</a>
                        </td>
                    </tr>
                    <tr v-if="stationData.enableOnDemand">
                        <td>{{ $gettext('On-Demand Media') }}</td>
                        <td>
                            <a
                                :href="profileData.publicOnDemandUri"
                                target="_blank"
                            >{{ profileData.publicOnDemandUri }}</a>
                        </td>
                    </tr>
                    <tr>
                        <td>{{ $gettext('Podcasts') }}</td>
                        <td>
                            <a
                                :href="profileData.publicPodcastsUri"
                                target="_blank"
                            >{{ profileData.publicPodcastsUri }}</a>
                        </td>
                    </tr>
                    <tr>
                        <td>{{ $gettext('Schedule') }}</td>
                        <td>
                            <a
                                :href="profileData.publicScheduleUri"
                                target="_blank"
                            >{{ profileData.publicScheduleUri }}</a>
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

    <embed-modal ref="$embedModal"/>
</template>

<script setup lang="ts">
import EnabledBadge from "~/components/Common/Badges/EnabledBadge.vue";
import {computed, useTemplateRef} from "vue";
import EmbedModal from "~/components/Stations/Profile/EmbedModal.vue";
import CardPage from "~/components/Common/CardPage.vue";
import {useUserAllowedForStation} from "~/functions/useUserallowedForStation.ts";
import useToggleFeature from "~/components/Stations/Profile/useToggleFeature";
import {StationPermissions} from "~/entities/ApiInterfaces.ts";
import {useStationData} from "~/functions/useStationQuery.ts";
import {useStationProfileData} from "~/components/Stations/Profile/useProfileQuery.ts";
import IconIcDesignServices from "~icons/ic/baseline-design-services";
import IconIcCheck from "~icons/ic/baseline-check";
import IconIcClose from "~icons/ic/baseline-close";
import IconIcCode from "~icons/ic/baseline-code";

const stationData = useStationData();
const profileData = useStationProfileData();

const {userAllowedForStation} = useUserAllowedForStation();

const $embedModal = useTemplateRef('$embedModal');

const doOpenEmbed = () => {
    $embedModal.value?.open();
};

const togglePublicPages = useToggleFeature(
    'enable_public_page',
    computed(() => stationData.value.enablePublicPages),
);
</script>
