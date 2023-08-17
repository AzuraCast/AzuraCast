<template>
    <div class="outside-card-header d-flex align-items-center">
        <div
            v-if="station.listen_url && hasStarted"
            class="flex-shrink-0 me-2"
        >
            <play-button
                class="btn-xl"
                :url="station.listen_url"
                is-stream
            />
        </div>
        <div class="flex-fill">
            <h2 class="display-6 m-0">
                {{ stationName }}<br>
                <small
                    v-if="stationDescription"
                    class="text-muted"
                >
                    {{ stationDescription }}
                </small>
            </h2>
        </div>
        <div
            v-if="userAllowedForStation(StationPermission.Profile)"
            class="flex-shrink-0 ms-3"
        >
            <router-link
                class="btn btn-primary"
                role="button"
                :to="{name: 'stations:profile:edit'}"
            >
                <icon icon="edit" />
                <span>
                    {{ $gettext('Edit Profile') }}
                </span>
            </router-link>
        </div>
    </div>
</template>

<script setup>
import Icon from '~/components/Common/Icon';
import PlayButton from "~/components/Common/PlayButton.vue";
import headerPanelProps from "~/components/Stations/Profile/headerPanelProps";
import {StationPermission, userAllowedForStation} from "~/acl";

const props = defineProps({
    ...headerPanelProps,
    station: {
        type: Object,
        required: true
    }
});
</script>
