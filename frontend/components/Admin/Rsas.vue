<template>
    <card-page
        header-id="hdr_install_rsas"
        :title="$gettext('Install RSAS')"
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
                                    $gettext('Rocket Streaming Audio Server (RSAS) is a closed-source alternative to Icecast as a broadcasting frontend. As it is proprietary, AzuraCast cannot distribute it, but you can install it via this page. If installed, stations can select it as their broadcasting software.')
                                }}
                            </p>

                            <p class="card-text">
                                {{ $gettext('In order to install RSAS:') }}
                            </p>

                            <ul>
                                <li>
                                    {{
                                        $gettext('Download the proper statically linked binary for your platform from the RSAS download page:')
                                    }}
                                    <br>
                                    <a
                                        href="https://www.rocketbroadcaster.com/streaming-audio-server/download/"
                                        target="_blank"
                                    >
                                        {{ $gettext('Download RSAS') }}
                                    </a>
                                </li>
                                <li>
                                    {{ $gettext('The file name should look like:') }}
                                    <br>
                                    <code>rsas-1.x.x-linux-(amd64/aarch64).tar.gz</code>
                                </li>
                                <li>
                                    {{
                                        $gettext('Upload the file on this page to automatically extract it into the proper directory.')
                                    }}
                                </li>
                                <li>
                                    {{
                                        $gettext('If using the paid version of RSAS, upload the license key file separately.')
                                    }}
                                </li>
                            </ul>
                        </fieldset>
                    </div>
                    <div class="col-md-5">
                        <div class="mb-3">
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
                                    {{ $gettext('RSAS is not currently installed on this installation.') }}
                                </p>
                            </fieldset>

                            <flow-upload
                                :target-url="apiUrl"
                                :valid-mime-types="['.tar.gz']"
                                @complete="relist"
                            />
                        </div>
                        <div>
                            <fieldset class="mb-3">
                                <legend>
                                    {{ $gettext('License Key') }}
                                </legend>

                                <p
                                    v-if="hasLicense"
                                    class="text-success card-text"
                                >
                                    {{ $gettext('License key is currently installed.') }}
                                </p>
                                <p
                                    v-else
                                    class="text-danger card-text"
                                >
                                    {{ $gettext('License key is not is not currently installed.') }}
                                </p>
                            </fieldset>

                            <flow-upload
                                :target-url="licenseUrl"
                                :valid-mime-types="['.key']"
                                @complete="relist"
                                accept
                            />

                            <div
                                v-if="hasLicense"
                                class="buttons block-buttons mt-3"
                            >
                                <button
                                    type="button"
                                    class="btn btn-danger"
                                    @click="doRemoveLicense"
                                >
                                    {{ $gettext('Remove License Key') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </loading>
        </div>
    </card-page>
</template>

<script setup lang="ts">
import FlowUpload from "~/components/Common/FlowUpload.vue";
import {computed, onMounted, ref} from "vue";
import {useTranslate} from "~/vendor/gettext";
import {useAxios} from "~/vendor/axios";
import Loading from "~/components/Common/Loading.vue";
import CardPage from "~/components/Common/CardPage.vue";
import {getApiUrl} from "~/router";
import {useDialog} from "~/functions/useDialog.ts";

const apiUrl = getApiUrl('/admin/rsas');
const licenseUrl = getApiUrl('/admin/rsas/license');

const isLoading = ref(true);
const version = ref(null);
const hasLicense = ref(false);

const {$gettext} = useTranslate();

const langInstalledVersion = computed(() => {
    return $gettext(
        'RSAS version "%{version}" is currently installed.',
        {
            version: version.value
        }
    );
});

const {axios} = useAxios();

const relist = () => {
    isLoading.value = true;
    axios.get(apiUrl.value).then(({data}) => {
        version.value = data.version;
        hasLicense.value = data.hasLicense;

        isLoading.value = false;
    });
};

const {confirmDelete} = useDialog();

const doRemoveLicense = () => {
    confirmDelete({
        title: $gettext('Remove RSAS license key?'),
        confirmButtonText: $gettext('Remove License Key')
    }).then((result) => {
        if (result.value) {
            axios.delete(licenseUrl.value).then(relist);
        }
    });
}

onMounted(relist);
</script>
