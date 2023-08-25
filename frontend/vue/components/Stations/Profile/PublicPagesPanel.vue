<template>
    <card-page header-id="hdr_public_pages">
        <template #header="{id}">
            <h3
                :id="id"
                class="card-title"
            >
                {{ $gettext('Public Pages') }}
                <enabled-badge :enabled="enablePublicPage" />
            </h3>
        </template>

        <template v-if="enablePublicPage">
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
                                :href="publicPageUri"
                                target="_blank"
                            >{{ publicPageUri }}</a>
                        </td>
                    </tr>
                    <tr v-if="stationSupportsStreamers && enableStreamers">
                        <td>{{ $gettext('Web DJ') }}</td>
                        <td>
                            <a
                                :href="publicWebDjUri"
                                target="_blank"
                            >{{ publicWebDjUri }}</a>
                        </td>
                    </tr>
                    <tr v-if="enableOnDemand">
                        <td>{{ $gettext('On-Demand Media') }}</td>
                        <td>
                            <a
                                :href="publicOnDemandUri"
                                target="_blank"
                            >{{ publicOnDemandUri }}</a>
                        </td>
                    </tr>
                    <tr>
                        <td>{{ $gettext('Podcasts') }}</td>
                        <td>
                            <a
                                :href="publicPodcastsUri"
                                target="_blank"
                            >{{ publicPodcastsUri }}</a>
                        </td>
                    </tr>
                    <tr>
                        <td>{{ $gettext('Schedule') }}</td>
                        <td>
                            <a
                                :href="publicScheduleUri"
                                target="_blank"
                            >{{ publicScheduleUri }}</a>
                        </td>
                    </tr>
                </tbody>
            </table>
        </template>

        <template #footer_actions>
            <template v-if="enablePublicPage">
                <a
                    class="btn btn-link text-secondary"
                    @click.prevent="doOpenEmbed"
                >
                    <icon :icon="IconCode" />
                    <span>
                        {{ $gettext('Embed Widgets') }}
                    </span>
                </a>
                <router-link
                    v-if="userAllowedForStation(StationPermission.Profile)"
                    class="btn btn-link text-secondary"
                    :to="{name: 'stations:branding'}"
                >
                    <icon :icon="IconBranding" />
                    <span>
                        {{ $gettext('Edit Branding') }}
                    </span>
                </router-link>
                <button
                    v-if="userAllowedForStation(StationPermission.Profile)"
                    type="button"
                    class="btn btn-link text-danger"
                    @click="togglePublicPages"
                >
                    <icon :icon="IconClose" />
                    <span>
                        {{ $gettext('Disable') }}
                    </span>
                </button>
            </template>
            <template v-else>
                <button
                    v-if="userAllowedForStation(StationPermission.Profile)"
                    type="button"
                    class="btn btn-link text-success"
                    @click="togglePublicPages"
                >
                    <icon :icon="IconCheck" />
                    <span>
                        {{ $gettext('Enable') }}
                    </span>
                </button>
            </template>
        </template>
    </card-page>

    <embed-modal
        v-bind="pickProps($props, embedModalProps)"
        ref="$embedModal"
    />
</template>

<script setup lang="ts">
import Icon from '~/components/Common/Icon.vue';
import EnabledBadge from "~/components/Common/Badges/EnabledBadge.vue";
import {ref} from "vue";
import EmbedModal from "~/components/Stations/Profile/EmbedModal.vue";
import publicPagesPanelProps from "~/components/Stations/Profile/publicPagesPanelProps";
import embedModalProps from "~/components/Stations/Profile/embedModalProps";
import {pickProps} from "~/functions/pickProps";
import CardPage from "~/components/Common/CardPage.vue";
import {StationPermission, userAllowedForStation} from "~/acl";
import useToggleFeature from "~/components/Stations/Profile/useToggleFeature";
import {IconBranding, IconCheck, IconClose, IconCode} from "~/components/Common/icons";

const props = defineProps({
    ...publicPagesPanelProps,
    ...embedModalProps
});

const $embedModal = ref<InstanceType<typeof EmbedModal> | null>(null);

const doOpenEmbed = () => {
    $embedModal.value?.open();
};

const togglePublicPages = useToggleFeature('enable_public_page', !props.enablePublicPage);
</script>
