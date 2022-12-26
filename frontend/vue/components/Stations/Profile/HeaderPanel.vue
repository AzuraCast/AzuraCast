<template>
    <div class="outside-card-header d-flex align-items-center mb-3">
        <div class="flex-shrink-0 mr-3" v-if="np.station.listen_url">
            <play-button icon-class="outlined xl" :url="np.station.listen_url" is-stream></play-button>
        </div>
        <div class="flex-fill">
            <h2 class="m-0">{{ stationName }}</h2>
            <h3 class="m-0" v-if="stationDescription">{{ stationDescription }}</h3>
        </div>
        <div class="flex-shrink-0 ml-3" v-if="userCanManageProfile">
            <a class="btn btn-primary btn-lg" role="button" :href="manageProfileUri">
                <icon icon="edit"></icon>
                {{ $gettext('Edit Profile') }}
            </a>
        </div>
    </div>
</template>

<script>
export default {
    inheritAttrs: false
};
</script>

<script setup>
import Icon from '~/components/Common/Icon';
import PlayButton from "~/components/Common/PlayButton.vue";
import headerPanelProps from "~/components/Stations/Profile/headerPanelProps";

const props = defineProps({
    ...headerPanelProps,
    np: Object
});
</script>
