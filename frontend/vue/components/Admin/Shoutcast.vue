<template>
    <section
        class="card"
        role="region"
        aria-labelledby="hdr_install_shoutcast"
    >
        <div class="card-header text-bg-primary">
            <h2
                id="hdr_install_shoutcast"
                class="card-title"
            >
                {{ $gettext('Install Shoutcast 2 DNAS') }}
            </h2>
        </div>

        <div class="card-body">
            <b-overlay
                variant="card"
                :show="loading"
            >
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
            </b-overlay>
        </div>
    </section>
</template>

<script setup>
import FlowUpload from "~/components/Common/FlowUpload";
import {computed, onMounted, ref} from "vue";
import {useTranslate} from "~/vendor/gettext";
import {useAxios} from "~/vendor/axios";

const props = defineProps({
    apiUrl: {
        type: String,
        required: true
    }
});

const loading = ref(true);
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
    loading.value = true;
    axios.get(props.apiUrl).then((resp) => {
        version.value = resp.data.version;
        loading.value = false;
    });
};

onMounted(relist);
</script>
