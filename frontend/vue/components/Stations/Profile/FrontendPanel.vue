<template>
    <section class="card mb-4" role="region" id="profile-frontend">
        <div class="card-header bg-primary-dark">
            <h3 class="card-title">
                {{ $gettext('Broadcasting Service') }}

                <running-badge :running="np.services.frontend_running"></running-badge>
                <br>
                <small>{{ frontendName }}</small>
            </h3>
        </div>

        <template v-if="userCanManageBroadcasting">
            <b-table-simple striped responsive>
                <tbody>
                <tr class="align-middle">
                    <td>
                        <a :href="frontendAdminUri" target="_blank">
                            {{ $gettext('Administration') }}
                        </a>
                    </td>
                    <td class="px-0">
                        <div>
                            {{ $gettext('Username:') }}
                            <span class="text-monospace">admin</span>
                        </div>
                        <div>
                            {{ $gettext('Password:') }}
                            <span class="text-monospace">{{ frontendAdminPassword }}</span>
                        </div>
                    </td>
                    <td class="px-0">
                        <copy-to-clipboard-button :text="frontendAdminPassword" hide-text></copy-to-clipboard-button>
                    </td>
                </tr>
                <tr class="align-middle">
                    <td>
                        {{ $gettext('Source') }}
                    </td>
                    <td class="px-0">
                        <div>
                            {{ $gettext('Username:') }}
                            <span class="text-monospace">source</span>
                        </div>
                        <div>
                            {{ $gettext('Password:') }}
                            <span class="text-monospace">{{ frontendSourcePassword }}</span>
                        </div>
                    </td>
                    <td class="px-0">
                        <copy-to-clipboard-button :text="frontendSourcePassword" hide-text></copy-to-clipboard-button>
                    </td>
                </tr>
                <tr class="align-middle">
                    <td>
                        {{ $gettext('Relay') }}
                    </td>
                    <td class="px-0">
                        <div>
                            {{ $gettext('Username:') }}
                            <span class="text-monospace">relay</span>
                        </div>
                        <div>
                            {{ $gettext('Password:') }}
                            <span class="text-monospace">{{ frontendRelayPassword }}</span>
                        </div>
                    </td>
                    <td class="px-0">
                        <copy-to-clipboard-button :text="frontendRelayPassword" hide-text></copy-to-clipboard-button>
                    </td>
                </tr>
                </tbody>
            </b-table-simple>

            <div class="card-actions" v-if="hasStarted">
                <a class="api-call no-reload btn btn-outline-secondary" :href="frontendRestartUri">
                    <icon icon="update"></icon>
                    {{ $gettext('Restart') }}
                </a>
                <a class="api-call no-reload btn btn-outline-success" v-show="!np.services.frontend_running"
                   :href="frontendStartUri">
                    <icon icon="play_arrow"></icon>
                    {{ $gettext('Start') }}
                </a>
                <a class="api-call no-reload btn btn-outline-danger" v-show="np.services.frontend_running"
                   :href="frontendStopUri">
                    <icon icon="stop"></icon>
                    {{ $gettext('Stop') }}
                </a>
            </div>
        </template>
    </section>
</template>

<script>
export const profileFrontendProps = {
    frontendType: String,
    frontendAdminUri: String,
    frontendAdminPassword: String,
    frontendSourcePassword: String,
    frontendRelayPassword: String,
    frontendRestartUri: String,
    frontendStartUri: String,
    frontendStopUri: String,
    hasStarted: Boolean,
    userCanManageBroadcasting: Boolean
};

export default {
    inheritAttrs: false
};
</script>

<script setup>
import {FRONTEND_ICECAST, FRONTEND_SHOUTCAST} from '~/components/Entity/RadioAdapters';
import CopyToClipboardButton from '~/components/Common/CopyToClipboardButton';
import Icon from '~/components/Common/Icon';
import RunningBadge from "~/components/Common/Badges/RunningBadge.vue";
import {computed} from "vue";

const props = defineProps({
    ...profileFrontendProps,
    np: Object
});

const frontendName = computed(() => {
    if (props.frontendType === FRONTEND_ICECAST) {
        return 'Icecast';
    } else if (props.frontendType === FRONTEND_SHOUTCAST) {
        return 'Shoutcast';
    }
    return '';
});
</script>
