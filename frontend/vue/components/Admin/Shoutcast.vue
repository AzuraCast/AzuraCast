<template>
    <card-page
        header-id="hdr_install_shoutcast"
        :title="$gettext('Install Shoutcast 2 DNAS')"
    >
        <div class="card-body">
            <loading :loading="isLoading">
                <div class="row g-3">
                    <div class="col-md-7">
                        <fieldset>
                            <legend>
                                {{ $gettext('Instructions') }}
                            </legend>

                            <p class="card-text">
                                {{
                                    $gettext('Shoutcast 2 DNAS is not free software, and its restrictive license does not allow AzuraCast to distribute the Shoutcast binary.')
                                }}
                            </p>

                            <p class="card-text">
                                {{ $gettext('In order to install Shoutcast:') }}
                            </p>

                            <ul>
                                <li>
                                    {{ $gettext('Download the Linux x64 binary from the Shoutcast Radio Manager:') }}
                                    <br>
                                    <a
                                        href="https://radiomanager.shoutcast.com/register/serverSoftwareFreemium"
                                        target="_blank"
                                    >
                                        {{ $gettext('Shoutcast Radio Manager') }}
                                    </a>
                                </li>
                                <li>
                                    {{ $gettext('The file name should look like:') }}
                                    <br>
                                    <code>sc_serv2_linux_x64-latest.tar.gz</code>
                                </li>
                                <li>
                                    {{
                                        $gettext('Upload the file on this page to automatically extract it into the proper directory.')
                                    }}
                                </li>
                            </ul>
                        </fieldset>
                    </div>
                    <div class="col-md-5">
                        <fieldset class="mb-3">
                            <legend>
                                {{ $gettext('Current Installed Version') }}
                            </legend>

                            <p
                                v-if="version"
                                class="text-success card-text"
                            >
                                {{ langInstalledVersion }}
                            </p>
                            <p
                                v-else
                                class="text-danger card-text"
                            >
                                {{ $gettext('Shoutcast 2 DNAS is not currently installed on this installation.') }}
                            </p>
                        </fieldset>

                        <flow-upload
                            :target-url="apiUrl"
                            @complete="relist"
                        />
                    </div>
                </div>
            </loading>
        </div>
    </card-page>
</template>

<script setup>
import FlowUpload from "~/components/Common/FlowUpload";
import {computed, onMounted, ref} from "vue";
import {useTranslate} from "~/vendor/gettext";
import {useAxios} from "~/vendor/axios";
import Loading from "~/components/Common/Loading.vue";
import CardPage from "~/components/Common/CardPage.vue";
import {getApiUrl} from "~/router";

const apiUrl = getApiUrl('/admin/shoutcast');

const isLoading = ref(true);
const version = ref(null);

const {$gettext} = useTranslate();

const langInstalledVersion = computed(() => {
    return $gettext(
        'Shoutcast version "%{ version }" is currently installed.',
        {
            version: version.value
        }
    );
});

const {axios} = useAxios();

const relist = () => {
    isLoading.value = true;
    axios.get(apiUrl.value).then((resp) => {
        version.value = resp.data.version;
        isLoading.value = false;
    });
};

onMounted(relist);
</script>
