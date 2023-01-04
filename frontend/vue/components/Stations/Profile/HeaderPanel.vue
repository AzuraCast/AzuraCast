<template>
    <div class="outside-card-header d-flex align-items-center mb-3">
        <div
            v-if="np.station.listen_url"
            class="flex-shrink-0 mr-3"
        >
            <play-button
                icon-class="outlined xl"
                :url="np.station.listen_url"
                is-stream
            />
        </div>
        <div class="flex-fill">
            <h2 class="m-0">
                {{ stationName }}
            </h2>
            <h3
                v-if="stationDescription"
                class="m-0"
            >
                {{ stationDescription }}
            </h3>
        </div>
        <div
            v-if="userCanManageProfile"
            class="flex-shrink-0 ml-3"
        >
            <a
                class="btn btn-primary btn-lg"
                role="button"
                :href="manageProfileUri"
            >
                <icon icon="edit" />
                {{ $gettext('Edit Profile') }}
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
    np: {
        type: Object,
        required: true
    }
});
</script>
