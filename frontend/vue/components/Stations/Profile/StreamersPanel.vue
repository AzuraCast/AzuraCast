<template>
    <section
        class="card mb-4"
        role="region"
    >
        <template v-if="enableStreamers">
            <div class="card-header bg-primary-dark">
                <h3 class="card-title">
                    {{ $gettext('Streamers/DJs') }}
                    <enabled-badge :enabled="true" />
                </h3>
            </div>
            <div
                v-if="userCanManageStreamers || userCanManageProfile"
                class="card-actions"
            >
                <a
                    v-if="userCanManageStreamers"
                    class="btn btn-outline-primary"
                    :href="streamersViewUri"
                >
                    <icon icon="settings" />
                    {{ $gettext('Manage') }}
                </a>
                <a
                    v-if="userCanManageProfile"
                    class="btn btn-outline-danger"
                    :data-confirm-title="$gettext('Disable streamers?')"
                    :href="streamersToggleUri"
                >
                    <icon icon="close" />
                    {{ $gettext('Disable') }}
                </a>
            </div>
        </template>
        <template v-else>
            <div class="card-header bg-primary-dark">
                <h3 class="card-title">
                    {{ $gettext('Streamers/DJs') }}
                    <enabled-badge :enabled="false" />
                </h3>
            </div>
            <div
                v-if="userCanManageProfile"
                class="card-actions"
            >
                <a
                    class="btn btn-outline-success"
                    :data-confirm-title="$gettext('Enable streamers?')"
                    :href="streamersToggleUri"
                >
                    <icon icon="check" />
                    {{ $gettext('Enable') }}
                </a>
            </div>
        </template>
    </section>
</template>

<script setup>
import Icon from "~/components/Common/Icon.vue";
import EnabledBadge from "~/components/Common/Badges/EnabledBadge.vue";
import streamersPanelProps from "~/components/Stations/Profile/streamersPanelProps";

const props = defineProps({
    ...streamersPanelProps
});
</script>
