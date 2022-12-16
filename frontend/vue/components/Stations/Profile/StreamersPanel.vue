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
                <a class="btn btn-outline-danger" v-if="userCanManageProfile" :data-confirm-title="langDisableStreamers" :href="streamersToggleUri">
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
                <a class="btn btn-outline-success" :data-confirm-title="langEnableStreamers" :href="streamersToggleUri">
                    <icon icon="check"></icon>
                    {{ $gettext('Enable') }}
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
