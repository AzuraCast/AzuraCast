<template>
    <div class="row">
        <div class="col col-md-8">
            <section class="card mb-4" role="region">
                <div class="card-header bg-primary-dark">
                    <h3 class="card-title">
                        {{ $gettext('Update Details') }}
                    </h3>
                </div>
                <div class="card-body">


                </div>
                <div class="card-actions buttons">
                    <a
                        class="btn btn-outline-info"
                        href="#"
                        @click.prevent="checkForUpdates()"
                    >
                        <icon icon="sync"></icon>
                        {{ $gettext('Check for Updates') }}
                    </a>
                </div>
            </section>
        </div>
        <div class="col col-md-4">
            <section class="card mb-4" role="region">
                <div class="card-header bg-primary-dark">
                    <h3 class="card-title">
                        {{ $gettext('Release Channel') }}
                    </h3>
                </div>
                <div class="card-body">
                    <p class="card-body">
                        {{ $gettext('Your installation is currently on this release channel:') }}
                    </p>
                    <p class="card-body typography-subheading">
                        {{ langReleaseChannel }}
                    </p>

                </div>
                <div class="card-actions buttons">
                    <a
                        class="btn btn-outline-info"
                        href="https://docs.azuracast.com/en/getting-started/updates/release-channel"
                    >
                        <icon icon="info"></icon>
                        {{ $gettext('About Release Channels') }}
                    </a>
                </div>
            </section>
        </div>
    </div>
    <div class="row">
        <div class="col col-md-6">
            <section class="card mb-4" role="region">
                <div class="card-header bg-primary-dark">
                    <h3 class="card-title">
                        {{ $gettext('Update AzuraCast via Web') }}
                    </h3>
                </div>
                <template v-if="enableWebUpdates">
                    <div class="card-body">
                        <p class="card-text">

                        </p>

                    </div>
                    <div class="card-actions buttons">
                        <a
                            class="btn btn-outline-success"
                            :data-confirm-title="$gettext('Update AzuraCast? Your installation will restart.')"
                            href="#"
                            @click.prevent="doUpdate()"
                        >
                            <icon icon="update"></icon>
                            {{ $gettext('Update AzuraCast via Web') }}
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
            <section class="card mb-4" role="region">
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
                    >
                        <icon icon="info"></icon>
                        {{ $gettext('Update Instructions') }}
                    </a>
                </div>
            </section>
        </div>
    </div>
</template>

<script setup>
import {computed, ref} from "vue";
import Icon from "~/components/InlinePlayer.vue";
import {useTranslate} from "~/vendor/gettext";
import {useNotify} from "~/vendor/bootstrapVue";
import {useAxios} from "~/vendor/axios";

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

const {wrapWithLoading, notifySuccess} = useNotify();
const {axios} = useAxios();

const checkForUpdates = () => {
    wrapWithLoading(
        axios.get(props.restartStatusUrl)
    ).then((resp) => {


    });
};

const doUpdate = () => {
    wrapWithLoading(
        axios.put(props.restartStatusUrl)
    ).then(() => {
        notifySuccess(
            $gettext('Update started. Your installation will restart shortly.')
        );
    });
}

</script>
