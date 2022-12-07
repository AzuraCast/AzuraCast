<template>
    <div>
        <b-form-fieldset>
            <b-form-row>
                <b-wrapped-form-group class="col-md-12" id="edit_form_backend_type"
                                      :field="form.backend_type">
                    <template #label="{lang}">
                        <translate :key="lang">AutoDJ Service</translate>
                    </template>
                    <template #description="{lang}">
                        <translate :key="lang">This software shuffles from playlists of music constantly and plays when
                            no other radio source is available.
                        </translate>
                    </template>
                    <template #default="props">
                        <b-form-radio-group stacked :id="props.id" :options="backendTypeOptions"
                                            v-model="props.field.$model">
                        </b-form-radio-group>
                    </template>
                </b-wrapped-form-group>
            </b-form-row>
        </b-form-fieldset>

        <b-form-fieldset v-if="isBackendEnabled">
            <b-form-fieldset>
                <b-form-row>
                    <b-wrapped-form-group class="col-md-7" id="edit_form_backend_crossfade_type"
                                          :field="form.backend_config.crossfade_type">
                        <template #label="{lang}">
                            <translate :key="lang">Crossfade Method</translate>
                        </template>
                        <template #description="{lang}">
                            <translate :key="lang">Choose a method to use when transitioning from one song to another.
                                Smart Mode considers the volume of the two tracks when fading for a smoother effect, but
                                requires more CPU resources.
                            </translate>
                        </template>
                        <template #default="props">
                            <b-form-radio-group stacked :id="props.id" :options="crossfadeOptions"
                                                v-model="props.field.$model">
                            </b-form-radio-group>
                        </template>
                    </b-wrapped-form-group>

                    <b-wrapped-form-group class="col-md-5" id="edit_form_backend_crossfade"
                                          :field="form.backend_config.crossfade" input-type="number"
                                          :input-attrs="{ min: '0.0', max: '30.0', step: '0.1' }">
                        <template #label="{lang}">
                            <translate :key="lang">Crossfade Duration (Seconds)</translate>
                        </template>
                        <template #description="{lang}">
                            <translate :key="lang">Number of seconds to overlap songs.</translate>
                        </template>
                    </b-wrapped-form-group>
                </b-form-row>
                <b-form-row>
                    <b-wrapped-form-group class="col-md-12" id="edit_form_backend_config_audio_processing_method"
                                          :field="form.backend_config.audio_processing_method">
                        <template #label="{lang}">
                            <translate :key="lang">Audio Processing Method</translate>
                        </template>
                        <template #description="{lang}">
                            <translate :key="lang">Choose a method to use for processing audio which produces a more
                                uniform and "full" sound for your station.
                            </translate>
                        </template>
                        <template #default="props">
                            <b-form-radio-group stacked :id="props.id" :options="audioProcessingOptions"
                                                v-model="props.field.$model">
                            </b-form-radio-group>
                        </template>
                    </b-wrapped-form-group>
                </b-form-row>
            </b-form-fieldset>

            <b-form-fieldset v-if="isStereoToolEnabled && isStereoToolInstalled">
                <template #label>
                    <translate key="lang_hdr_stereo_tool">Stereo Tool</translate>
                </template>
                <template #description>
                    <translate key="lang_stereo_tool_desc">Stereo Tool is an industry standard for software audio processing. For more information on how to configure it, please refer to the</translate>
                    <a href="https://www.thimeo.com/stereo-tool/" target="_blank">
                        <translate key="lang_stereo_tool_documentation_desc">Stereo Tool documentation.</translate>
                    </a>
                </template>

                <b-form-fieldset>
                    <b-form-row>
                        <b-wrapped-form-group class="col-md-7" id="edit_form_backend_stereo_tool_license_key"
                                              :field="form.backend_config.stereo_tool_license_key" input-type="text">
                            <template #label="{lang}">
                                <translate :key="lang">Stereo Tool License Key</translate>
                            </template>
                            <template #description="{lang}">
                                <translate :key="lang">Provide a valid license key from Thimeo. Functionality is limited without a license key.</translate>
                            </template>
                        </b-wrapped-form-group>

                        <b-form-markup class="col-md-5" id="edit_form_backend_stereo_tool_config">
                            <template #label="{lang}">
                                <translate :key="lang">Upload Stereo Tool Configuration</translate>
                            </template>

                            <p class="card-text">
                                <translate key="lang_stereotool_config">Upload a Stereo Tool configuration file from the "Broadcasting" submenu in the station profile.</translate>
                            </p>
                        </b-form-markup>
                    </b-form-row>
                </b-form-fieldset>
            </b-form-fieldset>

            <b-form-fieldset v-if="showAdvanced">
                <template #label>
                    <translate key="lang_hdr_advanced">Advanced Configuration</translate>
                </template>

                <b-form-row>
                    <b-wrapped-form-checkbox class="col-md-6"
                                             id="edit_form_backend_use_manual_autodj"
                                             :field="form.backend_config.use_manual_autodj" advanced>
                        <template #label="{lang}">
                            <translate :key="lang">Manual AutoDJ Mode</translate>
                        </template>
                        <template #description="{lang}">
                            <translate :key="lang">This mode disables AzuraCast's AutoDJ management, using Liquidsoap
                                itself to manage song playback. "Next Song" and some other features will not be
                                available.
                            </translate>
                        </template>
                    </b-wrapped-form-checkbox>

                    <b-wrapped-form-checkbox class="col-md-6"
                                             id="edit_form_backend_enable_replaygain_metadata"
                                             :field="form.backend_config.enable_replaygain_metadata" advanced>
                        <template #label="{lang}">
                            <translate :key="lang">Use Replaygain Metadata</translate>
                        </template>
                        <template #description="{lang}">
                            <translate :key="lang">Instruct Liquidsoap to use any replaygain metadata associated with a
                                song to control its volume level. This may increase CPU consumption.
                            </translate>
                        </template>
                    </b-wrapped-form-checkbox>

                    <b-wrapped-form-group class="col-md-6" id="edit_form_backend_telnet_port"
                                          :field="form.backend_config.telnet_port" input-type="number"
                                          :input-attrs="{ min: '0' }" advanced>
                        <template #label="{lang}">
                            <translate :key="lang">Customize Internal Request Processing Port</translate>
                        </template>
                        <template #description="{lang}">
                            <translate :key="lang">This port is not used by any external process. Only modify this port
                                if the assigned port is in use. Leave blank to automatically assign a port.
                            </translate>
                        </template>
                    </b-wrapped-form-group>

                    <b-wrapped-form-group class="col-md-6" id="edit_form_backend_autodj_queue_length"
                                          :field="form.backend_config.autodj_queue_length" input-type="number"
                                          :input-attrs="{ min: '2', max: '25' }" advanced>
                        <template #label="{lang}">
                            <translate :key="lang">AutoDJ Queue Length</translate>
                        </template>
                        <template #description="{lang}">
                            <translate :key="lang">This determines how many songs in advance the AutoDJ will
                                automatically fill the queue.
                            </translate>
                        </template>
                    </b-wrapped-form-group>

                    <b-wrapped-form-group class="col-md-6" id="edit_form_backend_charset"
                                          :field="form.backend_config.charset" advanced>
                        <template #label="{lang}">
                            <translate :key="lang">Character Set Encoding</translate>
                        </template>
                        <template #description="{lang}">
                            <translate :key="lang">For most cases, use the default UTF-8 encoding. The older ISO-8859-1
                                encoding can be used if accepting connections from Shoutcast 1 DJs or using other legacy
                                software.
                            </translate>
                        </template>
                        <template #default="props">
                            <b-form-radio-group stacked :id="props.id" :options="charsetOptions"
                                                v-model="props.field.$model">
                            </b-form-radio-group>
                        </template>
                    </b-wrapped-form-group>

                    <b-wrapped-form-group class="col-md-6" id="edit_form_backend_performance_mode"
                                          :field="form.backend_config.performance_mode" advanced>
                        <template #label="{lang}">
                            <translate :key="lang">Liquidsoap Performance Tuning</translate>
                        </template>
                        <template #description="{lang}">
                            <translate :key="lang">If your installation is constrained by CPU or memory, you can change
                                this setting to tune the resources used by Liquidsoap.
                            </translate>
                        </template>
                        <template #default="props">
                            <b-form-radio-group stacked :id="props.id" :options="performanceModeOptions"
                                                v-model="props.field.$model">
                            </b-form-radio-group>
                        </template>
                    </b-wrapped-form-group>

                    <b-wrapped-form-group class="col-md-6" id="edit_form_backend_duplicate_prevention_time_range"
                                          :field="form.backend_config.duplicate_prevention_time_range"
                                          input-type="number" :input-attrs="{ min: '0', max: '1440' }" advanced>
                        <template #label="{lang}">
                            <translate :key="lang">Duplicate Prevention Time Range (Minutes)</translate>
                        </template>
                        <template #description="{lang}">
                            <translate :key="lang">This specifies the time range (in minutes) of the song history that
                                the duplicate song prevention algorithm should take into account.
                            </translate>
                        </template>
                    </b-wrapped-form-group>
                </b-form-row>
            </b-form-fieldset>
        </b-form-fieldset>
    </div>
</template>

<script>
import BFormFieldset from "~/components/Form/BFormFieldset";
import BWrappedFormGroup from "~/components/Form/BWrappedFormGroup";
import {
    AUDIO_PROCESSING_LIQUIDSOAP,
    AUDIO_PROCESSING_NONE,
    AUDIO_PROCESSING_STEREO_TOOL,
    BACKEND_LIQUIDSOAP,
    BACKEND_NONE
} from "~/components/Entity/RadioAdapters";
import BWrappedFormCheckbox from "~/components/Form/BWrappedFormCheckbox";
import BFormMarkup from "~/components/Form/BFormMarkup";

export default {
    name: 'AdminStationsBackendForm',
    components: {BFormMarkup, BWrappedFormCheckbox, BWrappedFormGroup, BFormFieldset},
    props: {
        form: Object,
        station: Object,
        isStereoToolInstalled: {
            type: Boolean,
            default: true
        },
        showAdvanced: {
            type: Boolean,
            default: true
        },
    },
    computed: {
        backendTypeOptions() {
            return [
                {
                    text: this.$gettext('Use Liquidsoap on this server.'),
                    value: BACKEND_LIQUIDSOAP
                },
                {
                    text: this.$gettext('Do not use an AutoDJ service.'),
                    value: BACKEND_NONE
                }
            ];
        },
        isBackendEnabled() {
            return this.form.backend_type.$model !== BACKEND_NONE;
        },
        isStereoToolEnabled() {
            return this.form.backend_config.audio_processing_method.$model === AUDIO_PROCESSING_STEREO_TOOL;
        },
        crossfadeOptions() {
            return [
                {
                    text: this.$gettext('Smart Mode'),
                    value: 'smart',
                },
                {
                    text: this.$gettext('Normal Mode'),
                    value: 'normal',
                },
                {
                    text: this.$gettext('Disable Crossfading'),
                    value: 'none',
                }
            ];
        },
        audioProcessingOptions() {
            const audioProcessingOptions = [
                {
                    text: this.$gettext('Liquidsoap'),
                    value: AUDIO_PROCESSING_LIQUIDSOAP,
                },
                {
                    text: this.$gettext('Disable Processing'),
                    value: AUDIO_PROCESSING_NONE,
                }
            ];

            if (this.isStereoToolInstalled) {
                audioProcessingOptions.splice(1, 0,
                    {
                        text: this.$gettext('Stereo Tool'),
                        value: AUDIO_PROCESSING_STEREO_TOOL,
                    }
                )
            }

            return audioProcessingOptions;
        },
        recordStreamsOptions() {
            return [
                {
                    text: 'MP3',
                    value: 'mp3',
                },
                {
                    text: 'OGG Vorbis',
                    value: 'ogg',
                },
                {
                    text: 'OGG Opus',
                    value: 'opus',
                },
                {
                    text: 'AAC+ (MPEG4 HE-AAC v2)',
                    value: 'aac'
                }
            ];
        },
        recordBitrateOptions() {
            return [
                {text: '32', value: 32},
                {text: '48', value: 48},
                {text: '64', value: 64},
                {text: '96', value: 96},
                {text: '128', value: 128},
                {text: '192', value: 192},
                {text: '256', value: 256},
                {text: '320', value: 320}
            ];
        },
        charsetOptions() {
            return [
                {text: 'UTF-8', value: 'UTF-8'},
                {text: 'ISO-8859-1', value: 'ISO-8859-1'}
            ];
        },
        performanceModeOptions() {
            return [
                {
                    text: this.$gettext('Use Less Memory (Uses More CPU)'),
                    value: 'less_memory'
                },
                {
                    text: this.$gettext('Balanced'),
                    value: 'balanced'
                },
                {
                    text: this.$gettext('Use Less CPU (Uses More Memory)'),
                    value: 'less_cpu'
                },
                {
                    text: this.$gettext('Disable Optimizations'),
                    value: 'disabled'
                }
            ];
        }
    }
}
</script>
