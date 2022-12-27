<template>
    <section class="card mb-4" role="region">
        <template v-if="enablePublicPage">
            <div class="card-header bg-primary-dark">
                <h3 class="card-title">
                    {{ $gettext('Public Pages') }}
                    <enabled-badge :enabled="true"></enabled-badge>
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
            <div class="card-actions" v-if="userCanManageProfile">
                <a class="btn btn-outline-danger" @click.prevent="doOpenEmbed">
                    <icon icon="code"></icon>
                    {{ $gettext('Embed Widgets') }}
                </a>
                <a class="btn btn-outline-danger" :data-confirm-title="$gettext('Disable public pages?')"
                   :href="togglePublicPageUri">
                    <icon icon="close"></icon>
                    {{ $gettext('Disable') }}
                </a>
            </div>
            <embed-modal v-bind="$props" ref="embed_modal"></embed-modal>
        </template>
        <template v-else>
            <div class="card-header bg-primary-dark">
                <h3 class="card-title">
                    {{ $gettext('Public Pages') }}
                    <enabled-badge :enabled="false"></enabled-badge>
                </h3>
            </div>
            <div class="card-actions" v-if="userCanManageProfile">
                <a class="btn btn-outline-success" :data-confirm-title="$gettext('Enable public pages?')"
                   :href="togglePublicPageUri">
                    <icon icon="check"></icon>
                    {{ $gettext('Enable') }}
                </a>
            </div>
        </template>
    </section>
</template>

<script>
export default {
    inheritAttrs: false
};
</script>

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

const embed_modal = ref(); // Template Ref

const doOpenEmbed = () => {
    embed_modal.value.open();
};
</script>
