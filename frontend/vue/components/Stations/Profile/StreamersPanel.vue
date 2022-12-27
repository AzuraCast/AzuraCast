<template>
    <section class="card mb-4" role="region">
        <template v-if="enableStreamers">
            <div class="card-header bg-primary-dark">
                <h3 class="card-title">
                    {{ $gettext('Streamers/DJs') }}
                    <enabled-badge :enabled="true"></enabled-badge>
                </h3>
            </div>
            <div class="card-actions" v-if="userCanManageStreamers || userCanManageProfile">
                <a class="btn btn-outline-primary" v-if="userCanManageStreamers" :href="streamersViewUri">
                    <icon icon="settings"></icon>
                    {{ $gettext('Manage') }}
                </a>
                <a class="btn btn-outline-danger" v-if="userCanManageProfile"
                   :data-confirm-title="$gettext('Disable streamers?')" :href="streamersToggleUri">
                    <icon icon="close"></icon>
                    {{ $gettext('Disable') }}
                </a>
            </div>
        </template>
        <template v-else>
            <div class="card-header bg-primary-dark">
                <h3 class="card-title">
                    {{ $gettext('Streamers/DJs') }}
                    <enabled-badge :enabled="false"></enabled-badge>
                </h3>
            </div>
            <div class="card-actions" v-if="userCanManageProfile">
                <a class="btn btn-outline-success" :data-confirm-title="$gettext('Enable streamers?')"
                   :href="streamersToggleUri">
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
import Icon from "~/components/Common/Icon.vue";
import EnabledBadge from "~/components/Common/Badges/EnabledBadge.vue";
import streamersPanelProps from "~/components/Stations/Profile/streamersPanelProps";

const props = defineProps({
    ...streamersPanelProps
});
</script>
