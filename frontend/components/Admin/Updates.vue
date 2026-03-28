<template>
    <h2 class="outside-card-header mb-1">
        {{ $gettext('Update AzuraCast') }}
    </h2>

    <loading :loading="propsLoading" lazy>
        <div class="row row-of-cards">
            <div class="col col-md-8">
                <card-page
                    header-id="hdr_update_details"
                    :title="$gettext('Update Details')"
                >
                    <div class="card-body">
                        <div
                            v-if="needsUpdates"
                            class="text-warning"
                        >
                            {{
                                $gettext('Your installation needs to be updated. Updating is recommended for performance and security improvements.')
                            }}
                        </div>
                        <div
                            v-else
                            class="text-success"
                        >
                            {{
                                $gettext('Your installation is up to date! No update is required.')
                            }}
                        </div>
                    </div>

                    <template #footer_actions>
                        <button
                            type="button"
                            class="btn btn-info"
                            @click="checkForUpdates()"
                        >
                            <icon-ic-sync/>
                            {{ $gettext('Check for Updates') }}
                        </button>
                    </template>
                </card-page>
            </div>
            <div class="col col-md-4">
                <card-page
                    header-id="hdr_release_channel"
                    :title="$gettext('Release Channel')"
                >
                    <div class="card-body">
                        <p class="card-text">
                            {{ $gettext('Your installation is currently on this release channel:') }}
                        </p>
                        <p class="card-text typography-subheading">
                            {{ langReleaseChannel }}
                        </p>
                    </div>

                    <template #footer_actions>
                        <a
                            class="btn btn-info"
                            href="/docs/getting-started/updates/release-channels/"
                            target="_blank"
                        >
                            <icon-ic-info/>

                            {{ $gettext('About Release Channels') }}
                        </a>
                    </template>
                </card-page>
            </div>
        </div>
        <div class="row">
            <div class="col col-md-6">
                <card-page
                    header-id="hdr_update_via_web"
                    :title="$gettext('Update AzuraCast via Web')"
                >
                    <template v-if="props && props.enableWebUpdates">
                        <div class="card-body">
                            <p class="card-text">
                                {{
                                    $gettext('For simple updates where you want to keep your current configuration, you can update directly via your web browser. You will be disconnected from the web interface and listeners will be disconnected from all stations.')
                                }}
                            </p>
                            <p class="card-text">
                                {{
                                    $gettext('Backing up your installation is strongly recommended before any update.')
                                }}
                            </p>
                        </div>
                    </template>
                    <template v-else>
                        <div class="card-body">
                            <p class="card-text">
                                {{
                                    $gettext('Web updates are not available for your installation. To update your installation, perform the manual update process instead.')
                                }}
                            </p>
                        </div>
                    </template>

                    <template
                        v-if="props && props.enableWebUpdates"
                        #footer_actions
                    >
                        <router-link
                            :to="{ name: 'admin:backups:index' }"
                            class="btn btn-dark"
                        >
                            <icon-ic-cloud-upload/>

                            <span>
                                {{ $gettext('Backup') }}
                            </span>
                        </router-link>
                        <button
                            type="button"
                            class="btn btn-success"
                            @click="doUpdate()"
                        >
                            <icon-ic-update/>

                            <span>
                                {{ $gettext('Update via Web') }}
                            </span>
                        </button>
                    </template>
                </card-page>
            </div>
            <div class="col col-md-6">
                <card-page
                    header-id="hdr_manual_updates"
                    :title="$gettext('Manual Updates')"
                >
                    <div class="card-body">
                        <p class="card-text">
                            {{
                                $gettext('To customize installation settings, or if automatic updates are disabled, you can follow our standard update instructions to update via your SSH console.')
                            }}
                        </p>

                        <a
                            class="btn btn-info"
                            href="/docs/getting-started/updates/"
                            target="_blank"
                        >
                            <icon-ic-info/>
                            <span>
                                {{ $gettext('Update Instructions') }}
                            </span>
                        </a>
                    </div>
                </card-page>
            </div>
        </div>
    </loading>
</template>

<script setup lang="ts">
import {computed} from "vue";
import {useTranslate} from "~/vendor/gettext";
import {useNotify} from "~/components/Common/Toasts/useNotify.ts";
import {useAxios} from "~/vendor/axios";
import CardPage from "~/components/Common/CardPage.vue";
import {useDialog} from "~/components/Common/Dialogs/useDialog.ts";
import {ApiAdminUpdateDetails, ApiAdminVueUpdateProps} from "~/entities/ApiInterfaces.ts";
import {useQuery} from "@tanstack/vue-query";
import {QueryKeys} from "~/entities/Queries.ts";
import Loading from "~/components/Common/Loading.vue";
import IconIcInfo from "~icons/ic/baseline-info";
import IconIcSync from "~icons/ic/baseline-sync";
import IconIcUpdate from "~icons/ic/baseline-update";
import IconIcCloudUpload from "~icons/ic/baseline-cloud-upload";
import {useApiRouter} from "~/functions/useApiRouter.ts";

const {getApiUrl} = useApiRouter();
const propsUrl = getApiUrl('/admin/vue/updates');
const updatesApiUrl = getApiUrl('/admin/updates');

const {axios} = useAxios();

const {data: props, isLoading: propsLoading} = useQuery<ApiAdminVueUpdateProps>({
    queryKey: [QueryKeys.AdminUpdates, 'props'],
    queryFn: async ({signal}) => {
        const {data} = await axios.get<ApiAdminVueUpdateProps>(propsUrl.value, {signal});
        return data;
    }
});

const {data: updates, refetch: checkForUpdates} = useQuery<ApiAdminUpdateDetails>({
    queryKey: [QueryKeys.AdminUpdates, 'updates'],
    queryFn: async ({signal}) => {
        const {data} = await axios.get<ApiAdminUpdateDetails>(updatesApiUrl.value, {signal});
        return data;
    },
    enabled: false,
    retry: false
});

const {$gettext} = useTranslate();

const langReleaseChannel = computed(() => {
    return (props.value?.releaseChannel === 'stable')
        ? $gettext('Stable')
        : $gettext('Rolling Release');
});

const needsUpdates = computed(() => {
    const updateInfo = props.value?.initialUpdateInfo ?? updates.value ?? {};

    if (props.value?.releaseChannel === 'stable') {
        return updateInfo?.needs_release_update ?? false;
    } else {
        return updateInfo?.needs_rolling_update ?? false;
    }
});

const {notifySuccess} = useNotify();
const {showAlert} = useDialog();

const doUpdate = async () => {
    const {value} = await showAlert({
        title: $gettext('Update AzuraCast? Your installation will restart.'),
        confirmButtonText: $gettext('Update via Web')
    });

    if (!value) {
        return;
    }

    await axios.put(updatesApiUrl.value);

    notifySuccess(
        $gettext('Update started. Your installation will restart shortly.')
    );
};
</script>
