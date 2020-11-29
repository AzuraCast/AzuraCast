<template>
    <div class="card mb-4">
        <div class="card-header bg-primary-dark d-flex align-items-center">
            <div class="flex-fill">
                <h2 class="card-title">{{ stationName }}</h2>
                <h3 class="card-subtitle">{{ stationDescription }}</h3>
            </div>
            <div class="flex-shrink-0" v-if="userCanManageProfile">
                <a class="btn btn-bg" role="button" :href="manageProfileUri">
                    <i class="material-icons" aria-hidden="true">edit</i>
                    <translate key="lang_profile_btn_edit">Edit Profile</translate>
                </a>
            </div>
        </div>
    </div>
</template>

<script>
export const profileHeaderProps = {
    props: {
        stationName: String,
        stationDescription: String,
        userCanManageProfile: Boolean,
        manageProfileUri: String
    }
};

export default {
    mixins: [profileHeaderProps]
};
</script>
