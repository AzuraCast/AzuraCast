<template>
    <section
        class="card"
        role="region"
        aria-labelledby="hdr_streamers"
    >
        <template v-if="enableStreamers">
            <div class="card-header text-bg-primary">
                <h3
                    id="hdr_streamers"
                    class="card-title"
                >
                    {{ $gettext('Streamers/DJs') }}
                    <enabled-badge :enabled="true" />
                </h3>
            </div>
            <div
                v-if="userCanManageStreamers || userCanManageProfile"
                class="card-body buttons"
            >
                <a
                    v-if="userCanManageStreamers"
                    class="btn btn-link text-primary"
                    :href="streamersViewUri"
                >
                    <icon icon="settings" />
                    <span>
                        {{ $gettext('Manage') }}
                    </span>
                </a>
                <a
                    v-if="userCanManageProfile"
                    v-confirm-link="$gettext('Disable streamers?')"
                    class="btn btn-link text-danger"
                    :href="streamersToggleUri"
                >
                    <icon icon="close" />
                    <span>
                        {{ $gettext('Disable') }}
                    </span>
                </a>
            </div>
        </template>
        <template v-else>
            <div class="card-header text-bg-primary">
                <h3 class="card-title">
                    {{ $gettext('Streamers/DJs') }}
                    <enabled-badge :enabled="false" />
                </h3>
            </div>
            <div
                v-if="userCanManageProfile"
                class="card-body buttons"
            >
                <a
                    v-confirm-link="$gettext('Enable streamers?')"
                    class="btn btn-link text-success"
                    :href="streamersToggleUri"
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
import Icon from "~/components/Common/Icon.vue";
import EnabledBadge from "~/components/Common/Badges/EnabledBadge.vue";
import streamersPanelProps from "~/components/Stations/Profile/streamersPanelProps";

const props = defineProps({
    ...streamersPanelProps
});
</script>
