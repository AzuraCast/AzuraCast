<template>
    <div class="outside-card-header d-flex align-items-center">
        <div
            v-if="station.listen_url && hasStarted"
            class="flex-shrink-0 me-2"
        >
            <play-button
                class="btn-lg"
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
            v-if="userCanManageProfile"
            class="flex-shrink-0 ms-3"
        >
            <a
                class="btn btn-primary btn-lg"
                role="button"
                :href="manageProfileUri"
            >
                <icon icon="edit" />
                <span>
                    {{ $gettext('Edit Profile') }}
                </span>
            </a>
        </div>
    </div>
</template>

<script setup>
import Icon from '~/components/Common/Icon';
import PlayButton from "~/components/Common/PlayButton.vue";
import headerPanelProps from "~/components/Stations/Profile/headerPanelProps";

const props = defineProps({
    ...headerPanelProps,
    station: {
        type: Object,
        required: true
    }
});
</script>
