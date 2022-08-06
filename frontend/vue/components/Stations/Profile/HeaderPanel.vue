<template>
    <div class="outside-card-header d-flex align-items-center mb-1">
        <div class="flex-fill">
            <h2>{{ stationName }}</h2>
            <h3 v-if="stationDescription">{{ stationDescription }}</h3>
        </div>
        <div class="flex-shrink-0" v-if="userCanManageProfile">
            <a class="btn btn-primary btn-lg" role="button" :href="manageProfileUri">
                <icon icon="edit"></icon>
                <translate key="lang_profile_btn_edit">Edit Profile</translate>
            </a>
        </div>
    </div>
</template>

<script>
import Icon from '~/components/Common/Icon';

export const profileHeaderProps = {
    props: {
        stationName: String,
        stationDescription: String,
        userCanManageProfile: Boolean,
        manageProfileUri: String
    }
};

export default {
    inheritAttrs: false,
    components: {Icon},
    mixins: [profileHeaderProps]
};
</script>
