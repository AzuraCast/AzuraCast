<template>
    <section
        id="profile-frontend"
        class="card mb-4"
        role="region"
    >
        <div class="card-header bg-primary-dark">
            <h3 class="card-title">
                {{ $gettext('Broadcasting Service') }}

                <running-badge :running="np.services.frontend_running" />
                <br>
                <small>{{ frontendName }}</small>
            </h3>
        </div>

        <template v-if="userCanManageBroadcasting">
            <b-table-simple
                striped
                responsive
            >
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
            </b-table-simple>

            <div
                v-if="hasStarted"
                class="card-actions"
            >
                <a
                    class="api-call no-reload btn btn-outline-secondary"
                    :href="frontendRestartUri"
                >
                    <icon icon="update" />
                    {{ $gettext('Restart') }}
                </a>
                <a
                    v-show="!np.services.frontend_running"
                    class="api-call no-reload btn btn-outline-success"
                    :href="frontendStartUri"
                >
                    <icon icon="play_arrow" />
                    {{ $gettext('Start') }}
                </a>
                <a
                    v-show="np.services.frontend_running"
                    class="api-call no-reload btn btn-outline-danger"
                    :href="frontendStopUri"
                >
                    <icon icon="stop" />
                    {{ $gettext('Stop') }}
                </a>
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

const props = defineProps({
    ...frontendPanelProps,
    np: {
        type: Object,
        required: true
    }
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
