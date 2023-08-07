<template>
    <card-page
        header-id="hdr_install_stereo_tool"
        :title="$gettext('Install Stereo Tool')"
    >
        <template #info>
            <p class="card-text">
                {{
                    $gettext('Stereo Tool is a popular, proprietary tool for software audio processing. Using Stereo Tool, you can customize the sound of your stations using preset configuration files.')
                }}
            </p>
        </template>

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
                                    $gettext('Stereo Tool can be resource-intensive for both CPU and Memory. Please ensure you have sufficient resources before proceeding.')
                                }}
                            </p>

                            <p class="card-text">
                                {{
                                    $gettext('Stereo Tool is not free software, and its restrictive license does not allow AzuraCast to distribute the Stereo Tool binary.')
                                }}
                            </p>

                            <p class="card-text">
                                {{ $gettext('In order to install Stereo Tool:') }}
                            </p>

                            <ul>
                                <li>
                                    {{
                                        $gettext('Download the appropriate binary from the Stereo Tool downloads page:')
                                    }}
                                    <br>
                                    <a
                                        href="https://www.thimeo.com/stereo-tool/download/"
                                        target="_blank"
                                    >
                                        {{ $gettext('Stereo Tool Downloads') }}
                                    </a>
                                </li>
                                <li>
                                    {{
                                        $gettext('For x86/64 installations, choose "x86/64 Linux Thimeo-ST plugin".')
                                    }}
                                </li>
                                <li>
                                    {{
                                        $gettext('For ARM (Raspberry Pi, etc.) installations, choose "Raspberry Pi Thimeo-ST plugin".')
                                    }}
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
                                {{ $gettext('Stereo Tool is not currently installed on this installation.') }}
                            </p>
                        </fieldset>

                        <flow-upload
                            :target-url="apiUrl"
                            @complete="relist"
                            @error="onError"
                        />

                        <div
                            v-if="version"
                            class="buttons block-buttons mt-3"
                        >
                            <button
                                type="button"
                                class="btn btn-danger"
                                @click="doDelete"
                            >
                                {{ $gettext('Uninstall') }}
                            </button>
                        </div>
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
import {useNotify} from "~/functions/useNotify";
import {useAxios} from "~/vendor/axios";
import Loading from "~/components/Common/Loading.vue";
import CardPage from "~/components/Common/CardPage.vue";
import {useSweetAlert} from "~/vendor/sweetalert";
import {getApiUrl} from "~/router";

const apiUrl = getApiUrl('/admin/stereo_tool');

const isLoading = ref(true);
const version = ref(null);

const {$gettext} = useTranslate();

const langInstalledVersion = computed(() => {
    return $gettext(
        'Stereo Tool version %{ version } is currently installed.',
        {
            version: version.value
        }
    );
});

const {notifyError} = useNotify();

const onError = (file, message) => {
    notifyError(message);
};

const {axios} = useAxios();

const relist = () => {
    isLoading.value = true;
    axios.get(apiUrl.value).then((resp) => {
        version.value = resp.data.version;
        isLoading.value = false;
    });
};

const {confirmDelete} = useSweetAlert();

const doDelete = () => {
    confirmDelete().then((result) => {
        if (result.value) {
            axios.delete(apiUrl.value).then(relist);
        }
    });
}

onMounted(relist);
</script>
