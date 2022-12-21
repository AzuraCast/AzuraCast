<template>
    <b-form-fieldset>
        <div class="form-row">
            <b-wrapped-form-group class="col-md-12" id="edit_form_backend_type"
                                  :field="form.backend_type">
                <template #label>
                    {{ $gettext('AutoDJ Service') }}
                </template>
                <template #description>
                    {{
                        $gettext('This software shuffles from playlists of music constantly and plays when no other radio source is available.')
                    }}
                </template>
                <template #default="props">
                    <b-form-radio-group stacked :id="props.id" :options="backendTypeOptions"
                                        v-model="props.field.$model">
                    </b-form-radio-group>
                </template>
            </b-wrapped-form-group>
        </div>
    </b-form-fieldset>

    <b-form-fieldset v-if="isBackendEnabled">
        <b-form-fieldset>
            <div class="form-row">
                <b-wrapped-form-group class="col-md-7" id="edit_form_backend_crossfade_type"
                                      :field="form.backend_config.crossfade_type">
                    <template #label>
                        {{ $gettext('Crossfade Method') }}
                    </template>
                    <template #description>
                        {{
                            $gettext('Choose a method to use when transitioning from one song to another. Smart Mode considers the volume of the two tracks when fading for a smoother effect, but requires more CPU resources.')
                        }}
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
                    <template #label>
                        {{ $gettext('Crossfade Duration (Seconds)') }}
                    </template>
                    <template #description>
                        {{ $gettext('Number of seconds to overlap songs.') }}
                    </template>
                </b-wrapped-form-group>
            </div>
            <div class="form-row">
                <b-wrapped-form-group class="col-md-12" id="edit_form_backend_config_audio_processing_method"
                                      :field="form.backend_config.audio_processing_method">
                    <template #label>
                        {{ $gettext('Audio Processing Method') }}
                    </template>
                    <template #description>
                        {{
                            $gettext('Choose a method to use for processing audio which produces a more uniform and "full" sound for your station.')
                        }}
                    </template>
                    <template #default="props">
                        <b-form-radio-group stacked :id="props.id" :options="audioProcessingOptions"
                                            v-model="props.field.$model">
                        </b-form-radio-group>
                    </template>
                </b-wrapped-form-group>
            </div>
        </b-form-fieldset>

        <b-form-fieldset v-if="isStereoToolEnabled && isStereoToolInstalled">
            <template #label>
                {{ $gettext('Stereo Tool') }}
            </template>
            <template #description>
                {{
                    $gettext('Stereo Tool is an industry standard for software audio processing. For more information on how to configure it, please refer to the')
                }}
                <a href="https://www.thimeo.com/stereo-tool/" target="_blank">
                    {{ $gettext('Stereo Tool documentation.') }}
                </a>
            </template>

            <b-form-fieldset>
                <div class="form-row">
                    <b-wrapped-form-group class="col-md-7" id="edit_form_backend_stereo_tool_license_key"
                                          :field="form.backend_config.stereo_tool_license_key" input-type="text">
                        <template #label>
                            {{ $gettext('Stereo Tool License Key') }}
                        </template>
                        <template #description>
                            {{
                                $gettext('Provide a valid license key from Thimeo. Functionality is limited without a license key.')
                            }}
                        </template>
                    </b-wrapped-form-group>

                    <b-form-markup class="col-md-5" id="edit_form_backend_stereo_tool_config">
                        <template #label>
                            {{ $gettext('Upload Stereo Tool Configuration') }}
                        </template>

                        <p class="card-text">
                            {{
                                $gettext('Upload a Stereo Tool configuration file from the "Broadcasting" submenu in the station profile.')
                            }}
                        </p>
                    </b-form-markup>
                </div>
            </b-form-fieldset>
        </b-form-fieldset>

        <b-form-fieldset v-if="showAdvanced">
            <template #label>
                {{ $gettext('Advanced Configuration') }}
            </template>

            <div class="form-row">
                <b-wrapped-form-checkbox class="col-md-6"
                                         id="edit_form_backend_use_manual_autodj"
                                         :field="form.backend_config.use_manual_autodj" advanced>
                    <template #label>
                        {{ $gettext('Manual AutoDJ Mode') }}
                    </template>
                    <template #description>
                        {{
                            $gettext('This mode disables AzuraCast\'s AutoDJ management, using Liquidsoap itself to manage song playback. "Next Song" and some other features will not be available.')
                        }}
                    </template>
                </b-wrapped-form-checkbox>

                <b-wrapped-form-checkbox class="col-md-6"
                                         id="edit_form_backend_enable_replaygain_metadata"
                                         :field="form.backend_config.enable_replaygain_metadata" advanced>
                    <template #label>
                        {{ $gettext('Use Replaygain Metadata') }}
                    </template>
                    <template #description>
                        {{
                            $gettext('Instruct Liquidsoap to use any replaygain metadata associated with a song to control its volume level. This may increase CPU consumption.')
                        }}
                    </template>
                </b-wrapped-form-checkbox>

                <b-wrapped-form-group class="col-md-6" id="edit_form_backend_telnet_port"
                                      :field="form.backend_config.telnet_port" input-type="number"
                                      :input-attrs="{ min: '0' }" advanced>
                    <template #label>
                        {{ $gettext('Customize Internal Request Processing Port') }}
                    </template>
                    <template #description>
                        {{
                            $gettext('This port is not used by any external process. Only modify this port if the assigned port is in use. Leave blank to automatically assign a port.')
                        }}
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group class="col-md-6" id="edit_form_backend_autodj_queue_length"
                                      :field="form.backend_config.autodj_queue_length" input-type="number"
                                      :input-attrs="{ min: '2', max: '25' }" advanced>
                    <template #label>
                        {{ $gettext('AutoDJ Queue Length') }}
                    </template>
                    <template #description>
                        {{
                            $gettext('This determines how many songs in advance the AutoDJ will automatically fill the queue.')
                        }}
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group class="col-md-6" id="edit_form_backend_charset"
                                      :field="form.backend_config.charset" advanced>
                    <template #label>
                        {{ $gettext('Character Set Encoding') }}
                    </template>
                    <template #description>
                        {{
                            $gettext('For most cases, use the default UTF-8 encoding. The older ISO-8859-1 encoding can be used if accepting connections from Shoutcast 1 DJs or using other legacy software.')
                        }}
                    </template>
                    <template #default="props">
                        <b-form-radio-group stacked :id="props.id" :options="charsetOptions"
                                            v-model="props.field.$model">
                        </b-form-radio-group>
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group class="col-md-6" id="edit_form_backend_performance_mode"
                                      :field="form.backend_config.performance_mode" advanced>
                    <template #label>
                        {{ $gettext('Liquidsoap Performance Tuning') }}
                    </template>
                    <template #description>
                        {{
                            $gettext('If your installation is constrained by CPU or memory, you can change this setting to tune the resources used by Liquidsoap.')
                        }}
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
                    <template #label>
                        {{ $gettext('Duplicate Prevention Time Range (Minutes)') }}
                    </template>
                    <template #description>
                        {{
                            $gettext('This specifies the time range (in minutes) of the song history that the duplicate song prevention algorithm should take into account.')
                        }}
                    </template>
                </b-wrapped-form-group>
            </div>
        </b-form-fieldset>
    </b-form-fieldset>
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
