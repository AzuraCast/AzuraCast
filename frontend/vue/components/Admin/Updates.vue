<template>
    <h2 class="outside-card-header mb-1">
        {{ $gettext('Update AzuraCast') }}
    </h2>

    <div class="row">
        <div class="col col-md-8">
            <section
                class="card mb-4"
                role="region"
            >
                <div class="card-header bg-primary-dark">
                    <h3 class="card-title">
                        {{ $gettext('Update Details') }}
                    </h3>
                </div>
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
                <div class="card-actions buttons">
                    <a
                        class="btn btn-outline-info"
                        href="#"
                        @click.prevent="checkForUpdates()"
                    >
                        <icon icon="sync" />
                        {{ $gettext('Check for Updates') }}
                    </a>
                </div>
            </section>
        </div>
        <div class="col col-md-4">
            <section
                class="card mb-4"
                role="region"
            >
                <div class="card-header bg-primary-dark">
                    <h3 class="card-title">
                        {{ $gettext('Release Channel') }}
                    </h3>
                </div>
                <div class="card-body">
                    <p class="card-text">
                        {{ $gettext('Your installation is currently on this release channel:') }}
                    </p>
                    <p class="card-text typography-subheading">
                        {{ langReleaseChannel }}
                    </p>
                </div>
                <div class="card-actions buttons">
                    <a
                        class="btn btn-outline-info"
                        href="https://docs.azuracast.com/en/getting-started/updates/release-channels"
                        target="_blank"
                    >
                        <icon icon="info" />
                        {{ $gettext('About Release Channels') }}
                    </a>
                </div>
            </section>
        </div>
    </div>
    <div class="row">
        <div class="col col-md-6">
            <section
                class="card mb-4"
                role="region"
            >
                <div class="card-header bg-primary-dark">
                    <h3 class="card-title">
                        {{ $gettext('Update AzuraCast via Web') }}
                    </h3>
                </div>
                <template v-if="enableWebUpdates">
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
                    <div class="card-actions buttons">
                        <a
                            class="btn btn-outline-default"
                            :href="backupUrl"
                            target="_blank"
                        >
                            <icon icon="backup" />
                            {{ $gettext('Backup') }}
                        </a>
                        <a
                            class="btn btn-outline-success"
                            href="#"
                            @click.prevent="doUpdate()"
                        >
                            <icon icon="update" />
                            {{ $gettext('Update via Web') }}
                        </a>
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
            </section>
        </div>
        <div class="col col-md-6">
            <section
                class="card mb-4"
                role="region"
            >
                <div class="card-header bg-primary-dark">
                    <h3 class="card-title">
                        {{ $gettext('Manual Updates') }}
                    </h3>
                </div>
                <div class="card-body">
                    <p class="card-text">
                        {{
                            $gettext('To customize installation settings, or if automatic updates are disabled, you can follow our standard update instructions to update via your SSH console.')
                        }}
                    </p>
                </div>
                <div class="card-actions buttons">
                    <a
                        class="btn btn-outline-info"
                        href="https://docs.azuracast.com/en/getting-started/updates"
                        target="_blank"
                    >
                        <icon icon="info" />
                        {{ $gettext('Update Instructions') }}
                    </a>
                </div>
            </section>
        </div>
    </div>
</template>

<script setup>
import {computed, ref} from "vue";
import Icon from "~/components/Common/Icon.vue";
import {useTranslate} from "~/vendor/gettext";
import {useNotify} from "~/vendor/bootstrapVue";
import {useAxios} from "~/vendor/axios";
import {useSweetAlert} from "~/vendor/sweetalert";

const props = defineProps({
    releaseChannel: {
        type: String,
        required: true
    },
    initialUpdateInfo: {
        type: Object,
        default: () => {
            return {};
        }
    },
    backupUrl: {
        type: String,
        required: true
    },
    updatesApiUrl: {
        type: String,
        required: true
    },
    enableWebUpdates: {
        type: Boolean,
        required: true
    }
});

const updateInfo = ref(props.initialUpdateInfo);

const {$gettext} = useTranslate();

const langReleaseChannel = computed(() => {
    return (props.releaseChannel === 'stable')
        ? $gettext('Stable')
        : $gettext('Rolling Release');
});

const needsUpdates = computed(() => {
    if (props.releaseChannel === 'stable') {
        return updateInfo.value?.needs_release_update ?? true;
    } else {
        return updateInfo.value?.needs_rolling_update ?? true;
    }
});

const {wrapWithLoading, notifySuccess} = useNotify();
const {axios} = useAxios();

const checkForUpdates = () => {
    wrapWithLoading(
        axios.get(props.updatesApiUrl)
    ).then((resp) => {
        updateInfo.value = resp.data;
    });
};

const {showAlert} = useSweetAlert();

const doUpdate = () => {
    showAlert({
        title: $gettext('Update AzuraCast? Your installation will restart.')
    }).then((result) => {
        if (result.value) {
            wrapWithLoading(
                axios.put(props.updatesApiUrl)
            ).then(() => {
                notifySuccess(
                    $gettext('Update started. Your installation will restart shortly.')
                );
            });
        }
    });
};
</script>
