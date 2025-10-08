<template>
    <card-page
        id="profile-frontend"
        class="mb-4"
        header-id="hdr_frontend"
    >
        <template #header="{id}">
            <div class="d-flex align-items-center">
                <h3
                    :id="id"
                    class="flex-fill card-title my-0"
                >
                    {{ $gettext('Broadcasting Service') }}
                    <br>
                    <small>{{ frontendName }}</small>
                </h3>
                <div class="flex-shrink-0">
                    <running-badge :running="profileData.services.frontendRunning"/>
                </div>
            </div>
        </template>

        <template v-if="userAllowedForStation(StationPermissions.Broadcasting)">
            <div
                class="collapse"
                :class="(credentialsVisible) ? 'show' : ''"
                id="collapseFrontendCredentials"
                v-on="collapseListeners"
            >
                <table class="table table-striped table-responsive">
                    <tbody>
                        <tr class="align-middle">
                            <td>
                                <a
                                    :href="profileData.frontendAdminUri"
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
                                    <span class="text-monospace">{{ profileData.frontendAdminPassword }}</span>
                                </div>
                            </td>
                            <td class="px-0">
                                <copy-to-clipboard-button
                                    :text="profileData.frontendAdminPassword"
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
                                {{ profileData.frontendPort }}
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
                                    <span class="text-monospace">{{ profileData.frontendSourcePassword }}</span>
                                </div>
                            </td>
                            <td class="px-0">
                                <copy-to-clipboard-button
                                    :text="profileData.frontendSourcePassword"
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
                                    <span class="text-monospace">{{ profileData.frontendRelayPassword }}</span>
                                </div>
                            </td>
                            <td class="px-0">
                                <copy-to-clipboard-button
                                    :text="profileData.frontendRelayPassword"
                                    hide-text
                                />
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </template>

        <template
            v-if="userAllowedForStation(StationPermissions.Broadcasting)"
            #footer_actions
        >
            <button
                type="button"
                class="btn btn-link text-primary"
                data-bs-toggle="collapse"
                data-bs-target="#collapseFrontendCredentials"
                :aria-expanded="credentialsVisible ? 'true' : 'false'"
                aria-controls="collapseFrontendCredentials"
            >
                <icon-ic-more-horiz/>
                <span>
                    {{ langShowHideCredentials }}
                </span>
            </button>
            <template v-if="stationData.hasStarted">
                <button
                    type="button"
                    class="btn btn-link text-secondary"
                    @click="doRestart"
                >
                    <icon-ic-update/>

                    <span>
                        {{ $gettext('Restart') }}
                    </span>
                </button>
                <button
                    v-if="!profileData.services.frontendRunning"
                    type="button"
                    class="btn btn-link text-success"
                    @click="doStart()"
                >
                    <icon-ic-play-arrow/>
                    <span>
                        {{ $gettext('Start') }}
                    </span>
                </button>
                <button
                    v-if="profileData.services.frontendRunning"
                    type="button"
                    class="btn btn-link text-danger"
                    @click="doStop()"
                >
                    <icon-ic-stop/>

                    <span>
                        {{ $gettext('Stop') }}
                    </span>
                </button>
            </template>
        </template>
    </card-page>
</template>

<script setup lang="ts">
import CopyToClipboardButton from "~/components/Common/CopyToClipboardButton.vue";
import RunningBadge from "~/components/Common/Badges/RunningBadge.vue";
import {computed} from "vue";
import {useTranslate} from "~/vendor/gettext";
import CardPage from "~/components/Common/CardPage.vue";
import {useUserAllowedForStation} from "~/functions/useUserallowedForStation.ts";
import useOptionalStorage from "~/functions/useOptionalStorage";
import useMakeApiCall from "~/components/Stations/Profile/useMakeApiCall.ts";
import {FrontendAdapters, StationPermissions} from "~/entities/ApiInterfaces.ts";
import {useStationData} from "~/functions/useStationQuery.ts";
import {useStationProfileData} from "~/components/Stations/Profile/useProfileQuery.ts";
import IconIcMoreHoriz from "~icons/ic/baseline-more-horiz";
import IconIcPlayArrow from "~icons/ic/baseline-play-arrow";
import IconIcStop from "~icons/ic/baseline-stop";
import IconIcUpdate from "~icons/ic/baseline-update";
import {useApiRouter} from "~/functions/useApiRouter.ts";

const stationData = useStationData();
const profileData = useStationProfileData();

const {userAllowedForStation} = useUserAllowedForStation();

const {getStationApiUrl} = useApiRouter();

const frontendRestartUri = getStationApiUrl('/frontend/restart');
const frontendStartUri = getStationApiUrl('/frontend/start');
const frontendStopUri = getStationApiUrl('/frontend/stop');

const credentialsVisible = useOptionalStorage<boolean>('station_show_frontend_credentials', false);

const collapseListeners = {
    ['hidden.bs.collapse']: () => {
        credentialsVisible.value = false;
    },
    ['shown.bs.collapse']: () => {
        credentialsVisible.value = true;
    },
};

const {$gettext} = useTranslate();

const langShowHideCredentials = computed(() => {
    return (credentialsVisible.value)
        ? $gettext('Hide Credentials')
        : $gettext('Show Credentials')
});

const frontendName = computed(() => {
    switch (stationData.value.frontendType) {
        case FrontendAdapters.Icecast:
            return 'Icecast';

        case FrontendAdapters.Rsas:
            return 'Rocket Streaming Audio Server (RSAS)';

        case FrontendAdapters.Shoutcast:
            return 'Shoutcast';

        default:
            return '';
    }
});

const isShoutcast = computed(() => {
    return stationData.value.frontendType === FrontendAdapters.Shoutcast;
});

const doRestart = useMakeApiCall(
    frontendRestartUri,
    {
        title: $gettext('Restart service?'),
        confirmButtonText: $gettext('Restart')
    }
);

const doStart = useMakeApiCall(
    frontendStartUri,
    {
        title: $gettext('Start service?'),
        confirmButtonText: $gettext('Start'),
        confirmButtonClass: 'btn-success'
    }
);

const doStop = useMakeApiCall(
    frontendStopUri,
    {
        title: $gettext('Stop service?'),
        confirmButtonText: $gettext('Stop'),
        confirmButtonClass: 'btn-danger'
    }
);
</script>
