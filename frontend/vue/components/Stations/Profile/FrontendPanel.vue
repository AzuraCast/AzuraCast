<template>
    <section class="card mb-4" role="region" id="profile-frontend">
        <div class="card-header bg-primary-dark">
            <h3 class="card-title">
                <translate key="lang_frontend_title">Broadcasting Service</translate>
                <small class="badge badge-pill badge-success" v-if="np.services.frontend_running" key="lang_frontend_running">Running</small>
                <small class="badge badge-pill badge-danger" v-else key="lang_frontend_not_running">Not Running</small>
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
                            <translate key="lang_frontend_admin">Administration</translate>
                        </a>
                    </td>
                    <td class="px-0">
                        <div>
                            <translate key="lang_username">Username:</translate>
                            <span class="text-monospace">admin</span>
                        </div>
                        <div>
                            <translate key="lang_password">Password:</translate>
                            <span class="text-monospace">{{ frontendAdminPassword }}</span>
                        </div>
                    </td>
                    <td class="px-0">
                        <copy-to-clipboard-button :text="frontendAdminPassword" hide-text></copy-to-clipboard-button>
                    </td>
                </tr>
                <tr class="align-middle">
                    <td>
                        <translate key="lang_frontend_source">Source</translate>
                    </td>
                    <td class="px-0">
                        <div>
                            <translate key="lang_username">Username:</translate>
                            <span class="text-monospace">source</span>
                        </div>
                        <div>
                            <translate key="lang_password">Password:</translate>
                            <span class="text-monospace">{{ frontendSourcePassword }}</span>
                        </div>
                    </td>
                    <td class="px-0">
                        <copy-to-clipboard-button :text="frontendSourcePassword" hide-text></copy-to-clipboard-button>
                    </td>
                </tr>
                <tr class="align-middle">
                    <td>
                        <translate key="lang_frontend_relay">Relay</translate>
                    </td>
                    <td class="px-0">
                        <div>
                            <translate key="lang_username">Username:</translate>
                            <span class="text-monospace">relay</span>
                        </div>
                        <div>
                            <translate key="lang_password">Password:</translate>
                            <span class="text-monospace">{{ frontendRelayPassword }}</span>
                        </div>
                    </td>
                    <td class="px-0">
                        <copy-to-clipboard-button :text="frontendRelayPassword" hide-text></copy-to-clipboard-button>
                    </td>
                </tr>
                </tbody>
            </b-table-simple>

            <div class="card-actions">
                <a class="api-call no-reload btn btn-outline-secondary" :href="frontendRestartUri">
                    <icon icon="update"></icon>
                    <translate key="lang_profile_frontend_restart">Restart</translate>
                </a>
                <a class="api-call no-reload btn btn-outline-success" v-show="!np.services.frontend_running" :href="frontendStartUri">
                    <icon icon="play_arrow"></icon>
                    <translate key="lang_profile_frontend_start">Start</translate>
                </a>
                <a class="api-call no-reload btn btn-outline-danger" v-show="np.services.frontend_running" :href="frontendStopUri">
                    <icon icon="stop"></icon>
                    <translate key="lang_profile_frontend_stop">Stop</translate>
                </a>
            </div>
        </template>
    </section>
</template>

<script>
import {FRONTEND_ICECAST, FRONTEND_SHOUTCAST} from '~/components/Entity/RadioAdapters.js';
import CopyToClipboardButton from '~/components/Common/CopyToClipboardButton';
import Icon from '~/components/Common/Icon';

export const profileFrontendProps = {
    props: {
        frontendType: String,
        frontendAdminUri: String,
        frontendAdminPassword: String,
        frontendSourcePassword: String,
        frontendRelayPassword: String,
        frontendRestartUri: String,
        frontendStartUri: String,
        frontendStopUri: String,
        userCanManageBroadcasting: Boolean
    }
};

export default {
    inheritAttrs: false,
    components: {Icon, CopyToClipboardButton},
    mixins: [profileFrontendProps],
    props: {
        np: Object
    },
    computed: {
        frontendName() {
            if (this.frontendType === FRONTEND_ICECAST) {
                return 'Icecast';
            } else if (this.frontendType === FRONTEND_SHOUTCAST) {
                return 'SHOUTcast';
            }
            return '';
        },
        isIcecast () {
            return this.frontendType === FRONTEND_ICECAST;
        }
    }
};
</script>
