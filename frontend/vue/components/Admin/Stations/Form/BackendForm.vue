<template>
    <b-tab :title="langTabTitle">
        <b-form-fieldset>
            <b-wrapped-form-group class="col-md-12" id="edit_form_backend_type"
                                  :field="form.backend_type">
                <template #label>
                    <translate key="lang_edit_form_backend_type">Broadcasting Service</translate>
                </template>
                <template #description>
                    <translate key="lang_edit_form_backend_type_desc">This software shuffles from playlists of music constantly and plays when no other radio source is available.</translate>
                </template>
                <template #default="props">
                    <b-form-radio-group stacked :id="props.id" :options="backendTypeOptions"
                                        v-model="props.field.$model">
                    </b-form-radio-group>
                </template>
            </b-wrapped-form-group>
        </b-form-fieldset>

        <b-form-fieldset v-if="isBackendEnabled">
            <b-row>
                <b-wrapped-form-group class="col-md-8" id="edit_form_backend_crossfade_type"
                                      :field="form.backend_config.crossfade_type">
                    <template #label>
                        <translate key="lang_form_backend_crossfade_type">Crossfade Method</translate>
                    </template>
                    <template #description>
                        <translate key="lang_form_backend_crossfade_type_desc">Choose a method to use when transitioning from one song to another. Smart Mode considers the volume of the two tracks when fading for a smoother effect, but requires more CPU resources.</translate>
                    </template>

                    <template #default="props">
                        <b-form-radio-group stacked :id="props.id" :options="crossfadeOptions"
                                            v-model="props.field.$model">
                        </b-form-radio-group>
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group class="col-md-4" id="edit_form_backend_crossfade"
                                      :field="form.backend_config.crossfade" input-type="number"
                                      :input-attrs="{ min: '0.0', max: '30.0', step: '0.1' }">
                    <template #label>
                        <translate key="lang_form_backend_crossfade">Crossfade Duration (Seconds)</translate>
                    </template>
                    <template #description>
                        <translate
                            key="lang_form_backend_crossfade_desc">Number of seconds to overlap songs.</translate>
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group class="col-md-12"
                                      id="edit_form_backend_config_nrj"
                                      :field="form.backend_config.nrj">
                    <template #description>
                        <translate key="lang_edit_form_backend_config_nrj_desc">Compress and normalize your station's audio, producing a more uniform and "full" sound.</translate>
                    </template>
                    <template #default="props">
                        <b-form-checkbox :id="props.id" v-model="props.field.$model">
                            <translate
                                key="lang_edit_form_backend_config_nrj">Apply Compression and Normalization</translate>
                        </b-form-checkbox>
                    </template>
                </b-wrapped-form-group>
            </b-row>

            <b-form-fieldset>
                <template #label>
                    <translate key="lang_hdr_song_requests">Song Requests</translate>
                </template>

                <b-wrapped-form-group class="col-md-12" id="edit_form_enable_requests" :field="form.enable_requests">
                    <template #description>
                        <translate key="lang_edit_form_enable_requests_desc">Enable listeners to request a song for play on your station. Only songs that are already in your playlists are requestable.</translate>
                    </template>
                    <template #default="props">
                        <b-form-checkbox :id="props.id" v-model="props.field.$model">
                            <translate
                                key="lang_edit_form_enable_requests">Allow Song Requests</translate>
                        </b-form-checkbox>
                    </template>
                </b-wrapped-form-group>

                <b-form-fieldset v-if="form.enable_requests.$model">
                    <b-row>
                        <b-wrapped-form-group class="col-md-6" id="edit_form_request_delay"
                                              :field="form.request_delay" input-type="number"
                                              :input-attrs="{ min: '0', max: '1440' }">
                            <template #label>
                                <translate key="lang_form_request_delay">Request Minimum Delay (Minutes)</translate>
                            </template>
                            <template #description>
                                <translate key="lang_form_backend_request_delay_desc_1">If requests are enabled, this specifies the minimum delay (in minutes) between a request being submitted and being played. If set to zero, a minor delay of 15 seconds is applied to prevent request floods.</translate>
                                <br>
                                <translate key="lang_form_backend_request_delay_desc_2">Important: Some stream licensing rules require a minimum delay for requests. Check your local regulations for more information.</translate>
                            </template>
                        </b-wrapped-form-group>

                        <b-wrapped-form-group class="col-md-6" id="edit_form_request_threshold"
                                              :field="form.request_threshold" input-type="number"
                                              :input-attrs="{ min: '0', max: '1440' }">
                            <template #label>
                                <translate
                                    key="lang_form_request_threshold">Request Last Played Threshold (Minutes)</translate>
                            </template>
                            <template #description>
                        <translate
                            key="lang_form_request_threshold_desc">This specifies the minimum time (in minutes) between a song playing on the radio and being available to request again. Set to 0 for no threshold.</translate>
                            </template>
                        </b-wrapped-form-group>
                    </b-row>
                </b-form-fieldset>
            </b-form-fieldset>

        </b-form-fieldset>

    </b-tab>

    'enable_streamers' => [
    'toggle',
    [
    'label' => __('Allow Streamers / DJs'),
    'description' => __(
    'If enabled, streamers (or DJs) will be able to connect directly to your stream and broadcast live music that
    interrupts the AutoDJ stream.'
    ),
    'selected_text' => __('Yes'),
    'deselected_text' => __('No'),
    'default' => false,
    'form_group_class' => 'col-md-12',
    ],
    ],

    StationBackendConfiguration::RECORD_STREAMS => [
    'toggle',
    [
    'label' => __('Record Live Broadcasts'),
    'description' => __(
    'If enabled, AzuraCast will automatically record any live broadcasts made to this station to per-broadcast
    recordings.'
    ),
    'selected_text' => __('Yes'),
    'deselected_text' => __('No'),
    'default' => false,
    'belongsTo' => 'backend_config',
    'form_group_class' => 'col-md-4',
    ],
    ],

    StationBackendConfiguration::RECORD_STREAMS_FORMAT => [
    'radio',
    [
    'label' => __('Live Broadcast Recording Format'),
    'choices' => [
    StationMountInterface::FORMAT_MP3 => 'MP3',
    StationMountInterface::FORMAT_OGG => 'OGG Vorbis',
    StationMountInterface::FORMAT_OPUS => 'OGG Opus',
    StationMountInterface::FORMAT_AAC => 'AAC+ (MPEG4 HE-AAC v2)',
    ],
    'default' => StationMountInterface::FORMAT_MP3,
    'belongsTo' => 'backend_config',
    'form_group_class' => 'col-md-4',
    ],
    ],

    'record_streams_bitrate' => [
    'radio',
    [
    'label' => __('Live Broadcast Recording Bitrate (kbps)'),
    'choices' => [
    32 => '32',
    48 => '48',
    64 => '64',
    96 => '96',
    128 => '128',
    192 => '192',
    256 => '256',
    320 => '320',
    ],
    'default' => 128,
    'belongsTo' => 'backend_config',
    'form_group_class' => 'col-md-4',
    ],
    ],

    'disconnect_deactivate_streamer' => [
    'number',
    [
    'label' => __('Deactivate Streamer on Disconnect (Seconds)'),
    'description' => __(
    'Number of seconds to deactivate station streamer on manual disconnect. Set to 0 to disable deactivation
    completely.'
    ),
    'default' => 0,
    'min' => '0',
    'step' => '1',
    'form_group_class' => 'col-md-4',
    ],
    ],

    StationBackendConfiguration::DJ_PORT => [
    'text',
    [
    'label' => __('Customize DJ/Streamer Port'),
    'label_class' => 'advanced',
    'description' => __(
    'No other program can be using this port. Leave blank to automatically assign a port.<br><b>Note:</b> The port after
    this one (n+1) will automatically be used for legacy connections.'
    ),
    'belongsTo' => 'backend_config',
    'form_group_class' => 'col-md-6',
    ],
    ],

    'dj_buffer' => [
    'number',
    [
    'label' => __('DJ/Streamer Buffer Time (Seconds)'),
    'description' => __(
    'The number of seconds of signal to store in case of interruption. Set to the lowest value that your DJs can use
    without stream interruptions.'
    ),
    'default' => 5,
    'min' => 0,
    'max' => 60,
    'step' => 1,
    'belongsTo' => 'backend_config',
    'form_group_class' => 'col-md-6',
    ],
    ],

    StationBackendConfiguration::DJ_MOUNT_POINT => [
    'text',
    [
    'label' => __('Customize DJ/Streamer Mount Point'),
    'label_class' => 'advanced',
    'description' => __(
    'If your streaming software requires a specific mount point path, specify it here. Otherwise, use the default.'
    ),
    'belongsTo' => 'backend_config',
    'default' => '/',
    'form_group_class' => 'col-md-6',
    ],
    ],

    StationBackendConfiguration::TELNET_PORT => [
    'text',
    [
    'label' => __('Customize Internal Request Processing Port'),
    'label_class' => 'advanced',
    'description' => __(
    'This port is not used by any external process. Only modify this port if the assigned port is in use. Leave blank to
    automatically assign a port.'
    ),
    'belongsTo' => 'backend_config',
    'form_group_class' => 'col-md-6',
    ],
    ],

    StationBackendConfiguration::USE_REPLAYGAIN => [
    'toggle',
    [
    'label' => __('Use Replaygain Metadata'),
    'label_class' => 'advanced',
    'belongsTo' => 'backend_config',
    'description' => __(
    'Instruct Liquidsoap to use any replaygain metadata associated with a song to control its volume level.'
    ),
    'selected_text' => __('Yes'),
    'deselected_text' => __('No'),
    'default' => false,
    'form_group_class' => 'col-md-6',
    ],
    ],

    StationBackendConfiguration::AUTODJ_QUEUE_LENGTH => [
    'number',
    [
    'label' => __('AutoDJ Queue Length'),
    'description' => __(
    'If using AzuraCast\'s AutoDJ, this determines how many songs in advance the AutoDJ will automatically fill the
    queue.'
    ),
    'default' => StationBackendConfiguration::DEFAULT_QUEUE_LENGTH,
    'min' => 1,
    'max' => 25,
    'belongsTo' => 'backend_config',
    'form_group_class' => 'col-md-6',
    ],
    ],

    StationBackendConfiguration::USE_MANUAL_AUTODJ => [
    'toggle',
    [
    'label' => __('Manual AutoDJ Mode'),
    'label_class' => 'advanced',
    'description' => __(
    'This mode disables AzuraCast\'s AutoDJ management, using Liquidsoap itself to manage song playback. "Next Song" and
    some other features will not be available.'
    ),
    'selected_text' => __('Yes'),
    'deselected_text' => __('No'),
    'default' => false,
    'belongsTo' => 'backend_config',
    'form_group_class' => 'col-md-6',
    ],
    ],

    StationBackendConfiguration::CHARSET => [
    'radio',
    [
    'label' => __('Character Set Encoding'),
    'label_class' => 'advanced',
    'description' => __(
    'For most cases, use the default UTF-8 encoding. The older ISO-8859-1 encoding can be used if accepting connections
    from SHOUTcast 1 DJs or using other legacy software.'
    ),
    'belongsTo' => 'backend_config',
    'default' => 'UTF-8',
    'choices' => [
    'UTF-8' => 'UTF-8',
    'ISO-8859-1' => 'ISO-8859-1',
    ],
    'form_group_class' => 'col-md-6',
    ],
    ],

    StationBackendConfiguration::DUPLICATE_PREVENTION_TIME_RANGE => [
    'number',
    [
    'label' => __('Duplicate Prevention Time Range (Minutes)'),
    'description' => __(
    'This specifies the time range (in minutes) of the song history that the duplicate song prevention algorithm should
    take into account.'
    ),
    'belongsTo' => 'backend_config',
    'default' => StationBackendConfiguration::DEFAULT_DUPLICATE_PREVENTION_TIME_RANGE,
    'min' => '0',
    'max' => '1440',
    'form_group_class' => 'col-md-6',
    ],
    ],
    ],
    ],
</template>

<script>
import BFormFieldset from "~/components/Form/BFormFieldset";
import BWrappedFormGroup from "~/components/Form/BWrappedFormGroup";
import {BACKEND_LIQUIDSOAP, BACKEND_NONE} from "~/components/Entity/RadioAdapters";

export default {
    name: 'AdminStationsBackendForm',
    components: {BWrappedFormGroup, BFormFieldset},
    props: {
        form: Object
    },
    computed: {
        langTabTitle() {
            return this.$gettext('AutoDJ');
        },
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
                    text: this.$gettext('Disable Crossfading')
                    value: 'none',
                }
            ];
        }
    }
}
</script>
