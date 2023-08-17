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
                    <div v-if="hasStereoToolConfiguration">
                        <div class="block-buttons pt-3">
                            <a
                                class="btn btn-block btn-dark"
                                :href="apiUrl"
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

<script setup>
import FlowUpload from '~/components/Common/FlowUpload';
import InfoCard from "~/components/Common/InfoCard";
import {ref} from "vue";
import {useMayNeedRestart} from "~/functions/useMayNeedRestart";
import {useNotify} from "~/functions/useNotify";
import {useAxios} from "~/vendor/axios";
import FormGroup from "~/components/Form/FormGroup.vue";
import FormMarkup from "~/components/Form/FormMarkup.vue";
import {getStationApiUrl} from "~/router";

const props = defineProps({
    recordHasStereoToolConfiguration: {
        type: Boolean,
        required: true
    }
});

const apiUrl = getStationApiUrl('/stereo_tool_config');

const hasStereoToolConfiguration = ref(props.recordHasStereoToolConfiguration);

const {mayNeedRestart} = useMayNeedRestart();

const onFileSuccess = () => {
    mayNeedRestart();
    hasStereoToolConfiguration.value = true;
};

const {wrapWithLoading, notifySuccess} = useNotify();
const {axios} = useAxios();

const deleteConfigurationFile = () => {
    wrapWithLoading(
        axios({
            method: 'DELETE',
            url: apiUrl.value
        })
    ).then(() => {
        mayNeedRestart();
        hasStereoToolConfiguration.value = false;
        notifySuccess();
    });
};
</script>
