<template>
    <section
        class="card"
        role="region"
        aria-labelledby="hdr_upload_stereotool_config"
    >
        <div class="card-header text-bg-primary">
            <h2
                id="hdr_upload_stereotool_config"
                class="card-title"
            >
                {{ $gettext('Upload Stereo Tool Configuration') }}
            </h2>
        </div>

        <info-card>
            <p class="card-text">
                {{ $gettext('Stereo Tool is an industry standard for software audio processing. For more information on how to configure it, please refer to the') }}
                <a
                    class="alert-link"
                    href="https://www.thimeo.com/stereo-tool/"
                    target="_blank"
                >
                    {{ $gettext('Stereo Tool documentation.') }}
                </a>
            </p>
        </info-card>

        <div class="card-body">
            <div class="row g-3">
                <form-group
                    id="stereo_tool_configuration_file"
                    class="col-md-6"
                >
                    <template #label>
                        {{ $gettext('Select Configuration File') }}
                    </template>
                    <template #description>
                        {{
                            $gettext('This configuration file should be a valid .sts file exported from Stereo Tool.')
                        }}
                    </template>

                    <flow-upload
                        :target-url="apiUrl"
                        :valid-mime-types="['text/plain']"
                        @success="onFileSuccess"
                    />
                </form-group>

                <form-markup
                    id="current_configuration_file"
                    class="col-md-6"
                >
                    <template #label>
                        {{ $gettext('Current Configuration File') }}
                    </template>
                    <div v-if="downloadUrl">
                        <div class="block-buttons pt-3">
                            <a
                                class="btn btn-block btn-dark"
                                :href="downloadUrl"
                                target="_blank"
                            >
                                {{ $gettext('Download') }}
                            </a>
                            <button
                                type="button"
                                class="btn btn-block btn-danger"
                                @click="deleteConfigurationFile"
                            >
                                {{ $gettext('Clear File') }}
                            </button>
                        </div>
                    </div>
                    <div v-else>
                        {{ $gettext('There is no Stereo Tool configuration file present.') }}
                    </div>
                </form-markup>
            </div>
        </div>
    </section>
</template>

<script setup lang="ts">
import FlowUpload from '~/components/Common/FlowUpload.vue';
import InfoCard from "~/components/Common/InfoCard.vue";
import {onMounted, ref} from "vue";
import {useMayNeedRestart} from "~/functions/useMayNeedRestart";
import {useNotify} from "~/functions/useNotify";
import {useAxios} from "~/vendor/axios";
import FormGroup from "~/components/Form/FormGroup.vue";
import FormMarkup from "~/components/Form/FormMarkup.vue";
import {getStationApiUrl} from "~/router";

const apiUrl = getStationApiUrl('/stereo_tool_config');

const downloadUrl = ref(null);

const {axios} = useAxios();

const relist = () => {
    axios.get(apiUrl.value).then((resp) => {
        downloadUrl.value = resp.data.links.download;
    });
};

onMounted(relist);

const {mayNeedRestart} = useMayNeedRestart();

const onFileSuccess = () => {
    mayNeedRestart();
    relist();
};

const {notifySuccess} = useNotify();

const deleteConfigurationFile = () => {
    axios.delete(apiUrl.value).then(() => {
        mayNeedRestart();
        notifySuccess();
        relist();
    });
};
</script>
