<template>
    <section
        id="profile-frontend"
        class="card mb-4"
        role="region"
        aria-labelledby="hdr_frontend"
    >
        <div class="card-header text-bg-primary">
            <h3
                id="hdr_frontend"
                class="card-title"
            >
                {{ $gettext('Broadcasting Service') }}

                <running-badge :running="frontendRunning" />
                <br>
                <small>{{ frontendName }}</small>
            </h3>
        </div>

        <template v-if="userCanManageBroadcasting">
            <div
                class="collapse"
                :class="(credentialsVisible) ? 'show' : ''"
            >
                <table class="table table-striped table-responsive">
                    <tbody>
                        <tr class="align-middle">
                            <td>
                                <a
                                    :href="frontendAdminUri"
                                    target="_blank"
                                >
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
                                <copy-to-clipboard-button
                                    :text="frontendAdminPassword"
                                    hide-text
                                />
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
                                <copy-to-clipboard-button
                                    :text="frontendSourcePassword"
                                    hide-text
                                />
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
                                <copy-to-clipboard-button
                                    :text="frontendRelayPassword"
                                    hide-text
                                />
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="card-body buttons">
                <a
                    class="btn btn-link text-primary"
                    @click.prevent="credentialsVisible = !credentialsVisible"
                >
                    <icon icon="unfold_more" />
                    <span>
                        {{ langShowHideCredentials }}
                    </span>
                </a>
                <template v-if="hasStarted">
                    <button
                        class="btn btn-link text-secondary"
                        @click="makeApiCall(frontendRestartUri)"
                    >
                        <icon icon="update" />
                        <span>
                            {{ $gettext('Restart') }}
                        </span>
                    </button>
                    <button
                        v-if="!frontendRunning"
                        class="btn btn-link text-success"
                        @click="makeApiCall(frontendStartUri)"
                    >
                        <icon icon="play_arrow" />
                        <span>
                            {{ $gettext('Start') }}
                        </span>
                    </button>
                    <button
                        v-if="frontendRunning"
                        class="btn btn-link text-danger"
                        @click="makeApiCall(frontendStopUri)"
                    >
                        <icon icon="stop" />
                        <span>
                            {{ $gettext('Stop') }}
                        </span>
                    </button>
                </template>
            </div>
        </template>
    </section>
</template>

<script setup>
import {FRONTEND_ICECAST, FRONTEND_SHOUTCAST} from '~/components/Entity/RadioAdapters';
import CopyToClipboardButton from '~/components/Common/CopyToClipboardButton';
import Icon from '~/components/Common/Icon';
import RunningBadge from "~/components/Common/Badges/RunningBadge.vue";
import {computed} from "vue";
import frontendPanelProps from "~/components/Stations/Profile/frontendPanelProps";
import {useLocalStorage} from "@vueuse/core";
import {useTranslate} from "~/vendor/gettext";

const props = defineProps({
    ...frontendPanelProps,
    frontendRunning: {
        type: Boolean,
        required: true
    }
});

const emit = defineEmits(['api-call']);

const credentialsVisible = useLocalStorage('station_show_frontend_credentials', false);

const {$gettext} = useTranslate();

const langShowHideCredentials = computed(() => {
    return (credentialsVisible.value)
        ? $gettext('Hide Credentials')
        : $gettext('Show Credentials')
});

const frontendName = computed(() => {
    if (props.frontendType === FRONTEND_ICECAST) {
        return 'Icecast';
    } else if (props.frontendType === FRONTEND_SHOUTCAST) {
        return 'Shoutcast';
    }
    return '';
});

const makeApiCall = (uri) => {
    emit('api-call', uri);
};
</script>
