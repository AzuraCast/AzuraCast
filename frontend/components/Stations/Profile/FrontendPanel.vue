<template>
    <card-page
        id="profile-frontend"
        class="mb-4"
        header-id="hdr_frontend"
    >
        <template #header="{id}">
            <h3
                :id="id"
                class="card-title"
            >
                {{ $gettext('Broadcasting Service') }}

                <running-badge :running="frontendRunning" />
                <br>
                <small>{{ frontendName }}</small>
            </h3>
        </template>

        <template v-if="userAllowedForStation(StationPermission.Broadcasting)">
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
                                {{ $gettext('Port') }}
                            </td>
                            <td
                                class="ps-0"
                                colspan="2"
                            >
                                {{ frontendPort }}
                                <div
                                    v-if="isShoutcast"
                                    class="form-text"
                                >
                                    {{
                                        $gettext('Some clients may require that you enter a port number that is either one above or one below this number.')
                                    }}
                                </div>
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
        </template>

        <template
            v-if="userAllowedForStation(StationPermission.Broadcasting)"
            #footer_actions
        >
            <a
                class="btn btn-link text-primary"
                @click.prevent="credentialsVisible = !credentialsVisible"
            >
                <icon :icon="IconMoreHoriz" />
                <span>
                    {{ langShowHideCredentials }}
                </span>
            </a>
            <template v-if="hasStarted">
                <button
                    type="button"
                    class="btn btn-link text-secondary"
                    @click="doRestart"
                >
                    <icon :icon="IconUpdate" />
                    <span>
                        {{ $gettext('Restart') }}
                    </span>
                </button>
                <button
                    v-if="!frontendRunning"
                    type="button"
                    class="btn btn-link text-success"
                    @click="doStart()"
                >
                    <icon :icon="IconPlay" />
                    <span>
                        {{ $gettext('Start') }}
                    </span>
                </button>
                <button
                    v-if="frontendRunning"
                    type="button"
                    class="btn btn-link text-danger"
                    @click="doStop()"
                >
                    <icon :icon="IconStop" />
                    <span>
                        {{ $gettext('Stop') }}
                    </span>
                </button>
            </template>
        </template>
    </card-page>
</template>

<script lang="ts">
export interface ProfileFrontendPanelParentProps {
    frontendType: string,
    frontendAdminUri: string,
    frontendAdminPassword: string,
    frontendSourcePassword: string,
    frontendRelayPassword: string,
    frontendPort: number,
    frontendRestartUri: string,
    frontendStartUri: string,
    frontendStopUri: string,
    hasStarted: boolean
}
</script>

<script setup lang="ts">
import {FrontendAdapter} from '~/entities/RadioAdapters';
import CopyToClipboardButton from '~/components/Common/CopyToClipboardButton.vue';
import Icon from '~/components/Common/Icon.vue';
import RunningBadge from "~/components/Common/Badges/RunningBadge.vue";
import {computed} from "vue";
import {useTranslate} from "~/vendor/gettext";
import CardPage from "~/components/Common/CardPage.vue";
import {StationPermission, userAllowedForStation} from "~/acl";
import useOptionalStorage from "~/functions/useOptionalStorage";
import {IconMoreHoriz, IconPlay, IconStop, IconUpdate} from "~/components/Common/icons";
import useMakeApiCall from "~/components/Stations/Profile/useMakeApiCall.ts";

defineOptions({
    inheritAttrs: false
});

interface ProfileFrontendPanelProps extends ProfileFrontendPanelParentProps {
    frontendRunning: boolean,
}

const props = defineProps<ProfileFrontendPanelProps>();

const credentialsVisible = useOptionalStorage<boolean>('station_show_frontend_credentials', false);

const {$gettext} = useTranslate();

const langShowHideCredentials = computed(() => {
    return (credentialsVisible.value)
        ? $gettext('Hide Credentials')
        : $gettext('Show Credentials')
});

const frontendName = computed(() => {
    switch (props.frontendType) {
        case FrontendAdapter.Icecast:
            return 'Icecast';

        case FrontendAdapter.Rsas:
            return 'Rocket Streaming Audio Server (RSAS)';

        case FrontendAdapter.Shoutcast:
            return 'Shoutcast';

        default:
            return '';
    }
});

const isShoutcast = computed(() => {
    return props.frontendType === FrontendAdapter.Shoutcast;
});

const doRestart = useMakeApiCall(
    props.frontendRestartUri,
    {
        title: $gettext('Restart service?'),
        confirmButtonText: $gettext('Restart')
    }
);

const doStart = useMakeApiCall(
    props.frontendStartUri,
    {
        title: $gettext('Start service?'),
        confirmButtonText: $gettext('Start'),
        confirmButtonClass: 'btn-success'
    }
);

const doStop = useMakeApiCall(
    props.frontendStopUri,
    {
        title: $gettext('Stop service?'),
        confirmButtonText: $gettext('Stop'),
        confirmButtonClass: 'btn-danger'
    }
);
</script>
