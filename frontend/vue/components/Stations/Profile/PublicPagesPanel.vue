<template>
    <section
        class="card"
        role="region"
        aria-labelledby="hdr_public_pages"
    >
        <template v-if="enablePublicPage">
            <div class="card-header text-bg-primary">
                <h3
                    id="hdr_public_pages"
                    class="card-title"
                >
                    {{ $gettext('Public Pages') }}
                    <enabled-badge :enabled="true" />
                </h3>
            </div>
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
            <div class="card-body buttons">
                <a
                    class="btn btn-secondary"
                    @click.prevent="doOpenEmbed"
                >
                    <icon icon="code" />
                    <span>
                        {{ $gettext('Embed Widgets') }}
                    </span>
                </a>
                <template v-if="userCanManageProfile">
                    <a
                        class="btn btn-secondary"
                        :href="brandingUri"
                    >
                        <icon icon="design_services" />
                        <span>
                            {{ $gettext('Edit Branding') }}
                        </span>
                    </a>
                    <a
                        v-confirm-link="$gettext('Disable public pages?')"
                        class="btn btn-danger"
                        :href="togglePublicPageUri"
                    >
                        <icon icon="close" />
                        <span>
                            {{ $gettext('Disable') }}
                        </span>
                    </a>
                </template>
            </div>
            <embed-modal
                v-bind="$props"
                ref="$embedModal"
            />
        </template>
        <template v-else>
            <div class="card-header text-bg-primary">
                <h3 class="card-title">
                    {{ $gettext('Public Pages') }}
                    <enabled-badge :enabled="false" />
                </h3>
            </div>
            <div
                v-if="userCanManageProfile"
                class="card-body"
            >
                <a
                    v-confirm-link="$gettext('Enable public pages?')"
                    class="btn btn-success"
                    :href="togglePublicPageUri"
                >
                    <icon icon="check" />
                    <span>
                        {{ $gettext('Enable') }}
                    </span>
                </a>
            </div>
        </template>
    </section>
</template>

<script setup>
import Icon from '~/components/Common/Icon';
import EnabledBadge from "~/components/Common/Badges/EnabledBadge.vue";
import {ref} from "vue";
import EmbedModal from "~/components/Stations/Profile/EmbedModal.vue";
import publicPagesPanelProps from "~/components/Stations/Profile/publicPagesPanelProps";
import embedModalProps from "~/components/Stations/Profile/embedModalProps";

const props = defineProps({
    ...publicPagesPanelProps,
    ...embedModalProps
});

const $embedModal = ref(); // Template Ref

const doOpenEmbed = () => {
    $embedModal.value.open();
};
</script>
