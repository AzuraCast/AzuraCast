<template>
    <section class="card mb-4" role="region">
        <template v-if="enableStreamers">
            <div class="card-header bg-primary-dark">
                <h3 class="card-title">
                    <translate key="lang_profile_streamers_title">Streamers/DJs</translate>
                    <enabled-badge :enabled="true"></enabled-badge>
                </h3>
            </div>
            <div class="card-actions" v-if="userCanManageStreamers || userCanManageProfile">
                <a class="btn btn-outline-primary" v-if="userCanManageStreamers" :href="streamersViewUri">
                    <icon icon="settings"></icon>
                    <translate key="lang_profile_streamers_manage">Manage</translate>
                </a>
                <a class="btn btn-outline-danger" v-if="userCanManageProfile" :data-confirm-title="langDisableStreamers" :href="streamersToggleUri">
                    <icon icon="close"></icon>
                    <translate key="lang_profile_streamers_disable">Disable</translate>
                </a>
            </div>
        </template>
        <template v-else>
            <div class="card-header bg-primary-dark">
                <h3 class="card-title">
                    <translate key="lang_profile_streamers_title">Streamers/DJs</translate>
                    <enabled-badge :enabled="false"></enabled-badge>
                </h3>
            </div>
            <div class="card-actions" v-if="userCanManageProfile">
                <a class="btn btn-outline-success" :data-confirm-title="langEnableStreamers" :href="streamersToggleUri">
                    <icon icon="check"></icon>
                    <translate key="lang_profile_streamers_enable">Enable</translate>
                </a>
            </div>
        </template>
    </section>
</template>

<script>
import Icon from '~/components/Common/Icon';
import EnabledBadge from "./Common/EnabledBadge.vue";

export const profileStreamersProps = {
    props: {
        enableStreamers: Boolean,
        userCanManageProfile: Boolean,
        userCanManageStreamers: Boolean,
        streamersViewUri: String,
        streamersToggleUri: String
    }
};

export default {
    inheritAttrs: false,
    components: {EnabledBadge, Icon},
    mixins: [profileStreamersProps],
    computed: {
        langDisableStreamers() {
            return this.$gettext('Disable streamers?');
        },
        langEnableStreamers() {
            return this.$gettext('Enable streamers?');
        }
    }
};
</script>
