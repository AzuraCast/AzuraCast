<template>
    <tab
        :label="$gettext('AutoDJ')"
        :item-header-class="tabClass"
    >
        <div class="row g-3 mb-3">
            <form-group-multi-check
                id="edit_form_backend_type"
                class="col-md-12"
                :field="r$.backend_type"
                :options="backendTypeOptions"
                stacked
                radio
                :label="$gettext('AutoDJ Service')"
                :description="$gettext('This software shuffles from playlists of music constantly and plays when no other radio source is available.')"
            />
        </div>

        <template v-if="isBackendEnabled">
            <template v-if="!isAutoCueEnabled">
                <div class="row g-3 mb-3">
                    <form-group-multi-check
                        id="edit_form_backend_crossfade_type"
                        class="col-md-7"
                        :field="r$.backend_config.crossfade_type"
                        :options="crossfadeOptions"
                        stacked
                        radio
                        :label="$gettext('Crossfade Method')"
                        :description="$gettext('Choose a method to use when transitioning from one song to another. Smart Mode considers the volume of the two tracks when fading for a smoother effect, but requires more CPU resources.')"
                    />

                    <form-group-field
                        id="edit_form_backend_crossfade"
                        class="col-md-5"
                        :field="r$.backend_config.crossfade"
                        input-type="number"
                        :input-attrs="{ min: '0.0', max: '30.0', step: '0.1' }"
                        :label="$gettext('Crossfade Duration (Seconds)')"
                        :description="$gettext('Number of seconds to overlap songs.')"
                    />
                </div>
            </template>

            <form-fieldset>
                <template #label>
                    {{ $gettext('Audio Processing') }}
                </template>
                <template #description>
                    {{
                        $gettext('Apply audio processors (like compressors, limiters, or equalizers) to your stream to create a more uniform sound or enhance the listening experience. Processing requires extra CPU resources, so it may slow down your server.')
                    }}
                    <a
                        href="/docs/help/optimizing/#disable-audio-post-processing"
                        target="_blank"
                    >{{ $gettext('Learn More about Post-processing CPU Impact') }}</a>
                </template>

                <div class="row g-3 mb-3">
                    <form-group-checkbox
                        id="edit_form_backend_config_enable_auto_cue"
                        class="col-md-12"
                        :field="r$.backend_config.enable_auto_cue"
                        :label="$gettext('Enable AutoCue (Beta)')"
                        high-cpu
                    >
                        <template #description>
                            {{
                                $gettext('AutoCue analyzes your music and automatically calculates cue points, fade points, and volume levels for a consistent listening experience.')
                            }}
                            <br>
                            <a
                                href="https://github.com/Moonbase59/autocue/"
                                target="_blank"
                            >{{ $gettext('Learn more about AutoCue') }}</a>
                        </template>
                    </form-group-checkbox>

                    <form-group-checkbox
                        v-if="!isAutoCueEnabled"
                        id="edit_form_backend_enable_replaygain_metadata"
                        class="col-md-12"
                        :field="r$.backend_config.enable_replaygain_metadata"
                        :label="$gettext('Enable ReplayGain')"
                        high-cpu
                    >
                        <template #description>
                            {{
                                $gettext('Calculate and use normalized volume level metadata for each track.')
                            }}
                        </template>
                    </form-group-checkbox>
                </div>

                <div class="row g-3 mb-3">
                    <form-group-multi-check
                        id="edit_form_backend_config_audio_processing_method"
                        class="col-md-6"
                        :field="r$.backend_config.audio_processing_method"
                        :options="audioProcessingOptions"
                        stacked
                        radio
                        :label="$gettext('Audio Post-processing Method')"
                        :description="$gettext('Select an option here to apply post-processing using an easy preset or tool. You can also manually apply post-processing by editing your Liquidsoap configuration manually.')"
                        high-cpu
                    />

                    <template v-if="isPostProcessingEnabled">
                        <form-group-checkbox
                            id="edit_form_backend_config_post_processing_include_live"
                            class="col-md-6"
                            :field="r$.backend_config.post_processing_include_live"
                            :label="$gettext('Apply Post-processing to Live Streams')"
                            :description="$gettext('Check this box to apply post-processing to all audio, including live streams. Uncheck this box to only apply post-processing to the AutoDJ.')"
                        />
                    </template>
                </div>

                <template v-if="isMasterMeEnabled">
                    <form-markup id="master_me_info">
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
                    </form-markup>

                    <div class="row g-3">
                        <form-group-multi-check
                            id="edit_form_backend_master_me_preset"
                            class="col-md-6"
                            :field="r$.backend_config.master_me_preset"
                            :options="masterMePresetOptions"
                            stacked
                            radio
                            :label="$gettext('Master_me Preset')"
                        />

                        <form-group-field
                            id="edit_form_backend_master_me_loudness_target"
                            class="col-md-6"
                            :field="r$.backend_config.master_me_loudness_target"
                            input-type="number"
                            :input-attrs="{ min: '-50', max: '0', step: '1' }"
                            :label="$gettext('Master_me Loudness Target (LUFS)')"
                            :description="$gettext('The average target loudness (measured in LUFS) for the broadcasted stream. Values between -14 and -18 LUFS are common for Internet radio stations.')"
                            clearable
                        />
                    </div>
                </template>

                <template v-if="isStereoToolEnabled && isStereoToolInstalled">
                    <form-markup id="stereo_tool_info">
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
                    </form-markup>

                    <div class="row mb-3 g-3">
                        <form-group-field
                            id="edit_form_backend_stereo_tool_license_key"
                            class="col-md-7"
                            :field="r$.backend_config.stereo_tool_license_key"
                            input-type="text"
                            :label="$gettext('Stereo Tool License Key')"
                            :description="$gettext('Provide a valid license key from Thimeo. Functionality is limited without a license key.')"
                        />

                        <form-markup
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
                        </form-markup>
                    </div>
                </template>
            </form-fieldset>

            <form-fieldset>
                <template #label>
                    {{ $gettext('Advanced Configuration') }}
                    <span class="badge small text-bg-primary ms-2">
                        {{ $gettext('Advanced') }}
                    </span>
                </template>

                <div class="row g-3">
                    <form-group-checkbox
                        id="edit_form_backend_config_share_encoders"
                        class="col-md-12"
                        :field="r$.backend_config.share_encoders"
                        :label="$gettext('Share Encoders Between Streams')"
                        :description="$gettext('If you have multiple streams that broadcast in the same format and bitrate, enabling this may significantly improve CPU consumption. Disable this setting if you encounter issues with shared encoding.')"
                    />

                    <form-group-checkbox
                        id="edit_form_backend_use_manual_autodj"
                        class="col-md-12"
                        :field="r$.backend_config.use_manual_autodj"
                        :label="$gettext('Manual AutoDJ Mode')"
                    >
                        <template #description>
                            {{
                                $gettext('This mode disables AzuraCast\'s AutoDJ management, using Liquidsoap itself to manage song playback. "next song" and some other features will not be available.')
                            }}
                        </template>
                    </form-group-checkbox>

                    <form-group-checkbox
                        id="edit_form_backend_config_write_playlists_to_liquidsoap"
                        class="col-md-12"
                        :field="r$.backend_config.write_playlists_to_liquidsoap"
                        :label="$gettext('Always Write Playlists to Liquidsoap')"
                        :description="$gettext('By default, all playlists are written to Liquidsoap as a backup in case the normal AutoDJ fails. This can affect CPU load, especially on startup. Disable to only write essential playlists to Liquidsoap.')"
                    />

                    <form-group-field
                        id="edit_form_backend_telnet_port"
                        class="col-md-6"
                        :field="r$.backend_config.telnet_port"
                        input-type="number"
                        :input-attrs="{ min: '0' }"
                        :label="$gettext('Customize Internal Request Processing Port')"
                        :description="$gettext('This port is not used by any external process. Only modify this port if the assigned port is in use. Leave blank to automatically assign a port.')"
                    />

                    <form-group-field
                        id="edit_form_backend_autodj_queue_length"
                        class="col-md-6"
                        :field="r$.backend_config.autodj_queue_length"
                        input-type="number"
                        :input-attrs="{ min: '2', max: '25' }"
                        :label="$gettext('AutoDJ Queue Length')"
                        :description="$gettext('This determines how many songs in advance the AutoDJ will automatically fill the queue.')"
                    />

                    <form-group-multi-check
                        id="edit_form_backend_charset"
                        class="col-md-6"
                        :field="r$.backend_config.charset"
                        :options="charsetOptions"
                        stacked
                        radio
                        :label="$gettext('Character Set Encoding')"
                        :description="$gettext('For most cases, use the default UTF-8 encoding. The older ISO-8859-1 encoding can be used if accepting connections from Shoutcast 1 DJs or using other legacy software.')"
                    />

                    <form-group-multi-check
                        id="edit_form_backend_performance_mode"
                        class="col-md-6"
                        :field="r$.backend_config.performance_mode"
                        :options="performanceModeOptions"
                        stacked
                        radio
                        :label="$gettext('Liquidsoap Performance Tuning')"
                        :description="$gettext('If your installation is constrained by CPU or memory, you can change this setting to tune the resources used by Liquidsoap.')"
                    />

                    <form-group-field
                        id="edit_form_backend_duplicate_prevention_time_range"
                        class="col-md-6"
                        :field="r$.backend_config.duplicate_prevention_time_range"
                        input-type="number"
                        :input-attrs="{ min: '0', max: '1440' }"
                        :label="$gettext('Duplicate Prevention Time Range (Minutes)')"
                        :description="$gettext('This specifies the time range (in minutes) of the song history that the duplicate song prevention algorithm should take into account.')"
                    />
                </div>
            </form-fieldset>
        </template>
    </tab>
</template>

<script setup lang="ts">
import FormFieldset from "~/components/Form/FormFieldset.vue";
import FormGroupField from "~/components/Form/FormGroupField.vue";
import FormGroupCheckbox from "~/components/Form/FormGroupCheckbox.vue";
import FormMarkup from "~/components/Form/FormMarkup.vue";
import {computed} from "vue";
import {useTranslate} from "~/vendor/gettext";
import FormGroupMultiCheck from "~/components/Form/FormGroupMultiCheck.vue";
import Tab from "~/components/Common/Tab.vue";
import {SimpleFormOptionInput} from "~/functions/objectToFormOptions.ts";
import {AudioProcessingMethods, BackendAdapters, CrossfadeModes, MasterMePresets} from "~/entities/ApiInterfaces.ts";
import {storeToRefs} from "pinia";
import {useAdminStationsForm} from "~/components/Admin/Stations/Form/form.ts";
import {useFormTabClass} from "~/functions/useFormTabClass.ts";

const props = defineProps<{
    isStereoToolInstalled: boolean
}>();

const {r$, form} = storeToRefs(useAdminStationsForm());

const tabClass = useFormTabClass(computed(() => r$.value.$groups.backendTab));

const isBackendEnabled = computed(() => {
    return form.value?.backend_type !== BackendAdapters.None;
});

const isStereoToolEnabled = computed(() => {
    return form.value?.backend_config?.audio_processing_method === (AudioProcessingMethods.StereoTool as string);
});

const isMasterMeEnabled = computed(() => {
    return form.value?.backend_config?.audio_processing_method === (AudioProcessingMethods.MasterMe as string);
});

const isPostProcessingEnabled = computed(() => {
    return form.value?.backend_config?.audio_processing_method !== (AudioProcessingMethods.None as string);
});

const isAutoCueEnabled = computed(() => {
    return form.value?.backend_config?.enable_auto_cue ?? false;
});

const {$gettext} = useTranslate();

const backendTypeOptions = computed<SimpleFormOptionInput>(() => {
    return [
        {
            text: $gettext('Use Liquidsoap on this server.'),
            value: BackendAdapters.Liquidsoap
        },
        {
            text: $gettext('Do not use an AutoDJ service.'),
            value: BackendAdapters.None
        }
    ];
});

const crossfadeOptions = computed<SimpleFormOptionInput>(() => {
    return [
        {
            text: $gettext('Smart Mode'),
            value: CrossfadeModes.Smart,
        },
        {
            text: $gettext('Normal Mode'),
            value: CrossfadeModes.Normal,
        },
        {
            text: $gettext('Disable Crossfading'),
            value: CrossfadeModes.Disabled
        }
    ];
});

const audioProcessingOptions = computed<SimpleFormOptionInput>(() => {
    const audioProcessingOptions: SimpleFormOptionInput = [
        {
            text: $gettext('No Post-processing'),
            value: AudioProcessingMethods.None
        },
        {
            text: $gettext('Basic Normalization and Compression'),
            value: AudioProcessingMethods.Liquidsoap
        },
        {
            text: $gettext('Master_me Post-processing'),
            value: AudioProcessingMethods.MasterMe
        },
    ];

    if (props.isStereoToolInstalled) {
        audioProcessingOptions.push(
            {
                text: $gettext('Stereo Tool'),
                value: AudioProcessingMethods.StereoTool
            }
        )
    }

    return audioProcessingOptions;
});

const charsetOptions = computed<SimpleFormOptionInput>(() => {
    return [
        {text: 'UTF-8', value: 'UTF-8'},
        {text: 'ISO-8859-1', value: 'ISO-8859-1'}
    ];
});

const masterMePresetOptions = computed<SimpleFormOptionInput>(() => {
    return [
        {
            text: $gettext('Music General'),
            value: MasterMePresets.MusicGeneral
        },
        {
            text: $gettext('Speech General'),
            value: MasterMePresets.SpeechGeneral
        },
        {
            text: $gettext('EBU R128'),
            value: MasterMePresets.EbuR128
        },
        {
            text: $gettext('Apple Podcasts'),
            value: MasterMePresets.ApplePodcasts
        },
        {
            text: $gettext('YouTube'),
            value: MasterMePresets.YouTube
        }
    ]
});

const performanceModeOptions = computed<SimpleFormOptionInput>(() => {
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
