<template>
    <section
        class="card mb-4"
        role="region"
    >
        <template v-if="enablePublicPage">
            <div class="card-header bg-primary-dark">
                <h3 class="card-title">
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
                            <a :href="publicPageUri">{{ publicPageUri }}</a>
                        </td>
                    </tr>
                    <tr v-if="stationSupportsStreamers && enableStreamers">
                        <td>{{ $gettext('Web DJ') }}</td>
                        <td>
                            <a :href="publicWebDjUri">{{ publicWebDjUri }}</a>
                        </td>
                    </tr>
                    <tr v-if="enableOnDemand">
                        <td>{{ $gettext('On-Demand Media') }}</td>
                        <td>
                            <a :href="publicOnDemandUri">{{ publicOnDemandUri }}</a>
                        </td>
                    </tr>
                    <tr>
                        <td>{{ $gettext('Podcasts') }}</td>
                        <td>
                            <a :href="publicPodcastsUri">{{ publicPodcastsUri }}</a>
                        </td>
                    </tr>
                    <tr>
                        <td>{{ $gettext('Schedule') }}</td>
                        <td>
                            <a :href="publicScheduleUri">{{ publicScheduleUri }}</a>
                        </td>
                    </tr>
                </tbody>
            </table>
            <div class="card-actions">
                <a
                    class="btn btn-outline-default"
                    @click.prevent="doOpenEmbed"
                >
                    <icon icon="code" />
                    {{ $gettext('Embed Widgets') }}
                </a>
                <template v-if="userCanManageProfile">
                    <a
                        class="btn btn-outline-default"
                        :href="brandingUri"
                    >
                        <icon icon="design_services" />
                        {{ $gettext('Edit Branding') }}
                    </a>
                    <a
                        class="btn btn-outline-danger"
                        :data-confirm-title="$gettext('Disable public pages?')"
                        :href="togglePublicPageUri"
                    >
                        <icon icon="close" />
                        {{ $gettext('Disable') }}
                    </a>
                </template>
            </div>
            <embed-modal
                v-bind="$props"
                ref="$embedModal"
            />
        </template>
        <template v-else>
            <div class="card-header bg-primary-dark">
                <h3 class="card-title">
                    {{ $gettext('Public Pages') }}
                    <enabled-badge :enabled="false" />
                </h3>
            </div>
            <div
                v-if="userCanManageProfile"
                class="card-actions"
            >
                <a
                    class="btn btn-outline-success"
                    :data-confirm-title="$gettext('Enable public pages?')"
                    :href="togglePublicPageUri"
                >
                    <icon icon="check" />
                    {{ $gettext('Enable') }}
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
