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
            <table class="table table-striped table-responsive mb-0">
                <colgroup>
                    <col width="30%">
                    <col width="70%">
                </colgroup>
                <tbody>
                <tr>
                    <td key="lang_frontend_admin_uri" v-translate>Administration URL</td>
                    <td>
                        <a :href="frontendAdminUri">{{ frontendAdminUri }}</a>
                    </td>
                </tr>
                <tr>
                    <td key="lang_frontend_admin_pw" v-translate>Administrator Password</td>
                    <td>
                        <span id="frontend_admin_pw">{{ frontendAdminPassword }}</span>
                        <copy-to-clipboard-button target="#frontend_admin_pw" hide-text></copy-to-clipboard-button>
                    </td>
                </tr>
                <tr>
                    <td key="lang_frontend_source_pw" v-translate>Source Password</td>
                    <td>
                        <span id="frontend_source_pw">{{ frontendSourcePassword }}</span>
                        <copy-to-clipboard-button target="#frontend_source_pw" hide-text></copy-to-clipboard-button>
                    </td>
                </tr>
                <tr v-if="isIcecast">
                    <td key="lang_frontend_relay_pw" v-translate>Relay Password</td>
                    <td>
                        <span id="frontend_relay_pw">{{ frontendRelayPassword }}</span>
                        <copy-to-clipboard-button target="#frontend_relay_pw" hide-text></copy-to-clipboard-button>
                    </td>
                </tr>
                </tbody>
            </table>

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
import { FRONTEND_ICECAST, FRONTEND_SHOUTCAST } from '../../Entity/RadioAdapters.js';
import CopyToClipboardButton from '../../Common/CopyToClipboardButton';
import Icon from '../../Common/Icon';

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
    components: { Icon, CopyToClipboardButton },
    mixins: [profileFrontendProps],
    props: {
        np: Object
    },
    computed: {
        frontendName () {
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
