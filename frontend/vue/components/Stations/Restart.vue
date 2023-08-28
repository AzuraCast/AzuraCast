<template>
    <h2 class="outside-card-header mb-1">
        {{ $gettext('Update Station Configuration') }}
    </h2>

    <div class="row">
        <div class="col col-md-6">
            <section
                class="card"
                role="region"
                aria-labelledby="hdr_soft_reload"
            >
                <div class="card-header text-bg-primary">
                    <h3
                        id="hdr_soft_reload"
                        class="card-title"
                    >
                        {{ $gettext('Reload Configuration') }}
                    </h3>
                </div>
                <div class="card-body">
                    <p class="card-text">
                        {{
                            $gettext('Stations using Icecast can soft-reload the station configuration, applying changes while keeping the stream broadcast running.')
                        }}
                    </p>

                    <p class="card-text has-text-weight-bold text-body-emphasis">
                        {{
                            $gettext('Reloading broadcasting will not disconnect your listeners.')
                        }}
                    </p>

                    <template v-if="canReload">
                        <p class="card-text text-success">
                            {{
                                $gettext('Your station supports reloading configuration.')
                            }}
                        </p>

                        <div class="buttons">
                            <button
                                type="button"
                                class="btn btn-warning"
                                :disabled="isLoading"
                                @click="makeApiCall(reloadUrl)"
                            >
                                {{ $gettext('Reload Configuration') }}
                            </button>
                        </div>
                    </template>
                    <template v-else>
                        <p class="card-text text-danger">
                            {{
                                $gettext('Your station does not support reloading configuration. Restart broadcasting instead to apply changes.')
                            }}
                        </p>
                    </template>
                </div>
            </section>
        </div>
        <div class="col col-md-6">
            <section
                class="card"
                role="region"
                aria-labelledby="hdr_restart_broadcasting"
            >
                <div class="card-header text-bg-primary">
                    <h3
                        id="hdr_restart_broadcasting"
                        class="card-title"
                    >
                        {{ $gettext('Restart Broadcasting') }}
                    </h3>
                </div>
                <div class="card-body">
                    <p class="card-text">
                        {{
                            $gettext('Restarting broadcasting will rewrite all configuration files and restart all services.')
                        }}
                    </p>

                    <p class="card-text has-text-weight-bold text-body-emphasis">
                        {{
                            $gettext('Restarting broadcasting will briefly disconnect your listeners.')
                        }}
                    </p>

                    <div class="buttons">
                        <button
                            type="button"
                            class="btn btn-warning"
                            :disabled="isLoading"
                            @click="makeApiCall(restartUrl)"
                        >
                            {{ $gettext('Restart Broadcasting') }}
                        </button>
                    </div>
                </div>
            </section>
        </div>
    </div>
</template>

<script setup lang="ts">
import {useTranslate} from "~/vendor/gettext";
import {useNotify} from "~/functions/useNotify";
import {useAxios} from "~/vendor/axios";
import {useSweetAlert} from "~/vendor/sweetalert";
import {ref} from "vue";
import {getStationApiUrl} from "~/router";
import {useRouter} from "vue-router";

const props = defineProps({
    canReload: {
        type: Boolean,
        required: true,
    }
});

const reloadUrl = getStationApiUrl('/reload');
const restartUrl = getStationApiUrl('/restart');

const isLoading = ref(false);

const {axios} = useAxios();
const {showAlert} = useSweetAlert();
const {notify} = useNotify();
const {$gettext} = useTranslate();

const router = useRouter();

const makeApiCall = (uri) => {
    showAlert({
        title: $gettext('Are you sure?')
    }).then((result) => {
        if (!result.value) {
            return;
        }

        isLoading.value = true;

        axios.post(uri).then((resp) => {
            notify(resp.data.formatted_message, {
                variant: (resp.data.success) ? 'success' : 'warning'
            });

            setTimeout(
                async () => {
                    await router.push({
                        name: 'stations:index'
                    });
                    await router.go(0);
                },
                2000
            );
        }).finally(() => {
            isLoading.value = false;
        });
    });
};
</script>
