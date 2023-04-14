<template>
    <b-form-fieldset>
        <div class="form-row">
            <b-wrapped-form-group
                id="edit_form_backend_type"
                class="col-md-12"
                :field="form.backend_type"
            >
                <template #label>
                    {{ $gettext('AutoDJ Service') }}
                </template>
                <template #description>
                    {{
                        $gettext('This software shuffles from playlists of music constantly and plays when no other radio source is available.')
                    }}
                </template>
                <template #default="slotProps">
                    <b-form-radio-group
                        :id="slotProps.id"
                        v-model="slotProps.field.$model"
                        stacked
                        :options="backendTypeOptions"
                    />
                </template>
            </b-wrapped-form-group>
        </div>
    </b-form-fieldset>

    <b-form-fieldset v-if="isBackendEnabled">
        <b-form-fieldset>
            <div class="form-row">
                <b-wrapped-form-group
                    id="edit_form_backend_crossfade_type"
                    class="col-md-7"
                    :field="form.backend_config.crossfade_type"
                >
                    <template #label>
                        {{ $gettext('Crossfade Method') }}
                    </template>
                    <template #description>
                        {{
                            $gettext('Choose a method to use when transitioning from one song to another. Smart Mode considers the volume of the two tracks when fading for a smoother effect, but requires more CPU resources.')
                        }}
                    </template>
                    <template #default="slotProps">
                        <b-form-radio-group
                            :id="slotProps.id"
                            v-model="slotProps.field.$model"
                            stacked
                            :options="crossfadeOptions"
                        />
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group
                    id="edit_form_backend_crossfade"
                    class="col-md-5"
                    :field="form.backend_config.crossfade"
                    input-type="number"
                    :input-attrs="{ min: '0.0', max: '30.0', step: '0.1' }"
                >
                    <template #label>
                        {{ $gettext('Crossfade Duration (Seconds)') }}
                    </template>
                    <template #description>
                        {{ $gettext('Number of seconds to overlap songs.') }}
                    </template>
                </b-wrapped-form-group>
            </div>
        </b-form-fieldset>

        <b-form-fieldset v-if="isBackendEnabled">
            <b-form-fieldset>
                <template #label>
                    {{ $gettext('Audio Post-processing') }}
                </template>
                <template #description>
                    {{
                        $gettext('Post-processing allows you to apply audio processors (like compressors, limiters, or equalizers) to your stream to create a more uniform sound or enhance the listening experience. Post-processing requires extra CPU resources, so it may slow down your server.')
                    }}
                </template>

                <b-form-fieldset>
                    <div class="form-row">
                        <b-wrapped-form-group
                            id="edit_form_backend_config_audio_processing_method"
                            class="col-md-6"
                            :field="form.backend_config.audio_processing_method"
                        >
                            <template #label>
                                {{ $gettext('Audio Post-processing Method') }}
                            </template>
                            <template #description>
                                {{
                                    $gettext('Select an option here to apply post-processing using an easy preset or tool. You can also manually apply post-processing by editing your Liquidsoap configuration manually.')
                                }}
                            </template>
                            <template #default="slotProps">
                                <b-form-radio-group
                                    :id="slotProps.id"
                                    v-model="slotProps.field.$model"
                                    stacked
                                    :options="audioProcessingOptions"
                                />
                            </template>
                        </b-wrapped-form-group>

                        <template v-if="isPostProcessingEnabled">
                            <b-wrapped-form-checkbox
                                id="edit_form_backend_config_post_processing_include_live"
                                class="col-md-6"
                                :field="form.backend_config.post_processing_include_live"
                            >
                                <template #label>
                                    {{ $gettext('Apply Post-processing to Live Streams') }}
                                </template>
                                <template #description>
                                    {{
                                        $gettext('Check this box to apply post-processing to all audio, including live streams. Uncheck this box to only apply post-processing to the AutoDJ.')
                                    }}
                                </template>
                            </b-wrapped-form-checkbox>
                        </template>
                    </div>
                </b-form-fieldset>

                <b-form-fieldset v-if="isMasterMeEnabled">
                    <b-form-markup id="master_me_info">
                        <template #label>
                            {{ $gettext('About Master_me') }}
                        </template>

                        <p class="card-text">
                            {{
                                $gettext('Master_me is an open-source automatic mastering plugin for streaming, podcasts and Internet radio.')
                            }}
                        </p>
                        <p class="card-text">
                            <a
                                href="https://github.com/trummerschlunk/master_me"
                                target="_blank"
                            >
                                {{ $gettext('Master_me Project Homepage') }}
                            </a>
                        </p>
                    </b-form-markup>

                    <b-form-fieldset>
                        <div class="form-row">
                            <b-wrapped-form-group
                                id="edit_form_backend_master_me_preset"
                                class="col-md-7"
                                :field="form.backend_config.master_me_preset"
                            >
                                <template #label>
                                    {{ $gettext('Master_me Preset') }}
                                </template>
                                <template #default="slotProps">
                                    <b-form-radio-group
                                        :id="slotProps.id"
                                        v-model="slotProps.field.$model"
                                        stacked
                                        :options="masterMePresetOptions"
                                    />
                                </template>
                            </b-wrapped-form-group>
                        </div>
                    </b-form-fieldset>
                </b-form-fieldset>

                <b-form-fieldset v-if="isStereoToolEnabled && isStereoToolInstalled">
                    <b-form-markup id="stereo_tool_info">
                        <template #label>
                            {{ $gettext('Stereo Tool') }}
                        </template>

                        <p class="card-text">
                            {{
                                $gettext('Stereo Tool is an industry standard for software audio processing. For more information on how to configure it, please refer to the')
                            }}
                            <a
                                href="https://www.thimeo.com/stereo-tool/"
                                target="_blank"
                            >
                                {{ $gettext('Stereo Tool documentation.') }}
                            </a>
                        </p>
                    </b-form-markup>

                    <b-form-fieldset>
                        <div class="form-row">
                            <b-wrapped-form-group
                                id="edit_form_backend_stereo_tool_license_key"
                                class="col-md-7"
                                :field="form.backend_config.stereo_tool_license_key"
                                input-type="text"
                            >
                                <template #label>
                                    {{ $gettext('Stereo Tool License Key') }}
                                </template>
                                <template #description>
                                    {{
                                        $gettext('Provide a valid license key from Thimeo. Functionality is limited without a license key.')
                                    }}
                                </template>
                            </b-wrapped-form-group>

                            <b-form-markup
                                id="edit_form_backend_stereo_tool_config"
                                class="col-md-5"
                            >
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
            </b-form-fieldset>
        </b-form-fieldset>

        <b-form-fieldset v-if="showAdvanced">
            <template #label>
                {{ $gettext('Advanced Configuration') }}
            </template>

            <div class="form-row">
                <b-wrapped-form-checkbox
                    id="edit_form_backend_use_manual_autodj"
                    class="col-md-6"
                    :field="form.backend_config.use_manual_autodj"
                    advanced
                >
                    <template #label>
                        {{ $gettext('Manual AutoDJ Mode') }}
                    </template>
                    <template #description>
                        {{
                            $gettext('This mode disables AzuraCast\'s AutoDJ management, using Liquidsoap itself to manage song playback. "Next Song" and some other features will not be available.')
                        }}
                    </template>
                </b-wrapped-form-checkbox>

                <b-wrapped-form-checkbox
                    id="edit_form_backend_enable_replaygain_metadata"
                    class="col-md-6"
                    :field="form.backend_config.enable_replaygain_metadata"
                    advanced
                >
                    <template #label>
                        {{ $gettext('Use Replaygain Metadata') }}
                    </template>
                    <template #description>
                        {{
                            $gettext('Instruct Liquidsoap to use any replaygain metadata associated with a song to control its volume level. This may increase CPU consumption.')
                        }}
                    </template>
                </b-wrapped-form-checkbox>

                <b-wrapped-form-group
                    id="edit_form_backend_telnet_port"
                    class="col-md-6"
                    :field="form.backend_config.telnet_port"
                    input-type="number"
                    :input-attrs="{ min: '0' }"
                    advanced
                >
                    <template #label>
                        {{ $gettext('Customize Internal Request Processing Port') }}
                    </template>
                    <template #description>
                        {{
                            $gettext('This port is not used by any external process. Only modify this port if the assigned port is in use. Leave blank to automatically assign a port.')
                        }}
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group
                    id="edit_form_backend_autodj_queue_length"
                    class="col-md-6"
                    :field="form.backend_config.autodj_queue_length"
                    input-type="number"
                    :input-attrs="{ min: '2', max: '25' }"
                    advanced
                >
                    <template #label>
                        {{ $gettext('AutoDJ Queue Length') }}
                    </template>
                    <template #description>
                        {{
                            $gettext('This determines how many songs in advance the AutoDJ will automatically fill the queue.')
                        }}
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group
                    id="edit_form_backend_charset"
                    class="col-md-6"
                    :field="form.backend_config.charset"
                    advanced
                >
                    <template #label>
                        {{ $gettext('Character Set Encoding') }}
                    </template>
                    <template #description>
                        {{
                            $gettext('For most cases, use the default UTF-8 encoding. The older ISO-8859-1 encoding can be used if accepting connections from Shoutcast 1 DJs or using other legacy software.')
                        }}
                    </template>
                    <template #default="slotProps">
                        <b-form-radio-group
                            :id="slotProps.id"
                            v-model="slotProps.field.$model"
                            stacked
                            :options="charsetOptions"
                        />
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group
                    id="edit_form_backend_performance_mode"
                    class="col-md-6"
                    :field="form.backend_config.performance_mode"
                    advanced
                >
                    <template #label>
                        {{ $gettext('Liquidsoap Performance Tuning') }}
                    </template>
                    <template #description>
                        {{
                            $gettext('If your installation is constrained by CPU or memory, you can change this setting to tune the resources used by Liquidsoap.')
                        }}
                    </template>
                    <template #default="slotProps">
                        <b-form-radio-group
                            :id="slotProps.id"
                            v-model="slotProps.field.$model"
                            stacked
                            :options="performanceModeOptions"
                        />
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group
                    id="edit_form_backend_duplicate_prevention_time_range"
                    class="col-md-6"
                    :field="form.backend_config.duplicate_prevention_time_range"
                    input-type="number"
                    :input-attrs="{ min: '0', max: '1440' }"
                    advanced
                >
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

<script setup>
import BFormFieldset from "~/components/Form/BFormFieldset.vue";
import BWrappedFormGroup from "~/components/Form/BWrappedFormGroup.vue";
import {
    AUDIO_PROCESSING_LIQUIDSOAP,
    AUDIO_PROCESSING_MASTER_ME,
    AUDIO_PROCESSING_NONE,
    AUDIO_PROCESSING_STEREO_TOOL,
    BACKEND_LIQUIDSOAP,
    BACKEND_NONE,
    MASTER_ME_PRESET_APPLE_PODCASTS,
    MASTER_ME_PRESET_EBU_R128, MASTER_ME_PRESET_MUSIC_GENERAL,
    MASTER_ME_PRESET_SPEECH_GENERAL,
    MASTER_ME_PRESET_YOUTUBE
} from "~/components/Entity/RadioAdapters";
import BWrappedFormCheckbox from "~/components/Form/BWrappedFormCheckbox.vue";
import BFormMarkup from "~/components/Form/BFormMarkup.vue";
import {computed} from "vue";
import {useTranslate} from "~/vendor/gettext";

const props = defineProps({
    form: {
        type: Object,
        required: true
    },
    station: {
        type: Object,
        required: true
    },
    isStereoToolInstalled: {
        type: Boolean,
        default: true
    },
    showAdvanced: {
        type: Boolean,
        default: true
    },
});

const isBackendEnabled = computed(() => {
    return props.form.backend_type.$model !== BACKEND_NONE;
});

const isStereoToolEnabled = computed(() => {
    return props.form.backend_config.audio_processing_method.$model === AUDIO_PROCESSING_STEREO_TOOL;
});

const isMasterMeEnabled = computed(() => {
    return props.form.backend_config.audio_processing_method.$model === AUDIO_PROCESSING_MASTER_ME;
});

const isPostProcessingEnabled = computed(() => {
    return props.form.backend_config.audio_processing_method.$model !== AUDIO_PROCESSING_NONE;
});

const {$gettext} = useTranslate();

const backendTypeOptions = computed(() => {
    return [
        {
            text: $gettext('Use Liquidsoap on this server.'),
            value: BACKEND_LIQUIDSOAP
        },
        {
            text: $gettext('Do not use an AutoDJ service.'),
            value: BACKEND_NONE
        }
    ];
});

const crossfadeOptions = computed(() => {
    return [
        {
            text: $gettext('Smart Mode'),
            value: 'smart',
        },
        {
            text: $gettext('Normal Mode'),
            value: 'normal',
        },
        {
            text: $gettext('Disable Crossfading'),
            value: 'none',
        }
    ];
});

const audioProcessingOptions = computed(() => {
    const audioProcessingOptions = [
        {
            text: $gettext('No Post-processing'),
            value: AUDIO_PROCESSING_NONE,
        },
        {
            text: $gettext('Basic Normalization and Compression'),
            value: AUDIO_PROCESSING_LIQUIDSOAP,
        },
        {
            text: $gettext('Master_me Post-processing'),
            value: AUDIO_PROCESSING_MASTER_ME,
        },
    ];

    if (props.isStereoToolInstalled) {
        audioProcessingOptions.push(
            {
                text: $gettext('Stereo Tool'),
                value: AUDIO_PROCESSING_STEREO_TOOL,
            }
        )
    }

    return audioProcessingOptions;
});

const charsetOptions = computed(() => {
    return [
        {text: 'UTF-8', value: 'UTF-8'},
        {text: 'ISO-8859-1', value: 'ISO-8859-1'}
    ];
});

const masterMePresetOptions = computed(() => {
    return [
        {
            text: $gettext('Music General'),
            value: MASTER_ME_PRESET_MUSIC_GENERAL
        },
        {
            text: $gettext('Speech General'),
            value: MASTER_ME_PRESET_SPEECH_GENERAL
        },
        {
            text: $gettext('EBU R128'),
            value: MASTER_ME_PRESET_EBU_R128
        },
        {
            text: $gettext('Apple Podcasts'),
            value: MASTER_ME_PRESET_APPLE_PODCASTS
        },
        {
            text: $gettext('YouTube'),
            value: MASTER_ME_PRESET_YOUTUBE
        }
    ]
});

const performanceModeOptions = computed(() => {
    return [
        {
            text: $gettext('Use Less Memory (Uses More CPU)'),
            value: 'less_memory'
        },
        {
            text: $gettext('Balanced'),
            value: 'balanced'
        },
        {
            text: $gettext('Use Less CPU (Uses More Memory)'),
            value: 'less_cpu'
        },
        {
            text: $gettext('Disable Optimizations'),
            value: 'disabled'
        }
    ];
});
</script>
