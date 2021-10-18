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

                <b-row>
                    <b-wrapped-form-group class="col-md-12" id="edit_form_enable_requests"
                                          :field="form.enable_requests">
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
                </b-row>

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

            <b-form-fieldset>
                <template #label>
                    <translate key="lang_hdr_streamers">Streamers / DJs</translate>
                </template>

                <b-row>
                    <b-wrapped-form-group class="col-md-12" id="edit_form_enable_streamers"
                                          :field="form.enable_streamers">
                        <template #description>
                            <translate key="lang_edit_form_enable_streamers_desc">If enabled, streamers (or DJs) will be able to connect directly to your stream and broadcast live music that interrupts the AutoDJ stream.</translate>
                        </template>
                        <template #default="props">
                            <b-form-checkbox :id="props.id" v-model="props.field.$model">
                                <translate
                                    key="lang_edit_form_enable_streamers">Allow Streamers / DJs</translate>
                            </b-form-checkbox>
                        </template>
                    </b-wrapped-form-group>
                </b-row>

                <b-form-fieldset v-if="form.enable_streamers.$model">
                    <b-row>
                        <b-wrapped-form-group class="col-md-12" id="edit_form_backend_record_streams"
                                              :field="form.backend_config.record_streams">
                            <template #description>
                                <translate key="lang_edit_form_backend_record_streams_desc">If enabled, AzuraCast will automatically record any live broadcasts made to this station to per-broadcast recordings.</translate>
                            </template>
                            <template #default="props">
                                <b-form-checkbox :id="props.id" v-model="props.field.$model">
                                    <translate
                                        key="lang_edit_form_backend_record_streams">Record Live Broadcasts</translate>
                                </b-form-checkbox>
                            </template>
                        </b-wrapped-form-group>
                    </b-row>

                    <b-form-fieldset v-if="form.backend_config.record_streams.$model">
                        <b-row>
                            <b-wrapped-form-group class="col-md-6" id="edit_form_backend_record_streams_format"
                                                  :field="form.backend_config.record_streams_format">
                                <template #label>
                                    <translate key="lang_form_backend_record_streams_format">Live Broadcast Recording Format</translate>
                                </template>

                                <template #default="props">
                                    <b-form-radio-group stacked :id="props.id" :options="recordStreamsOptions"
                                                        v-model="props.field.$model">
                                    </b-form-radio-group>
                                </template>
                            </b-wrapped-form-group>

                            <b-wrapped-form-group class="col-md-6" id="edit_form_backend_record_streams_bitrate"
                                                  :field="form.backend_config.record_streams_bitrate">
                                <template #label>
                                    <translate key="lang_form_backend_record_streams_bitrate">Live Broadcast Recording Bitrate (kbps)</translate>
                                </template>

                                <template #default="props">
                                    <b-form-radio-group stacked :id="props.id" :options="recordBitrateOptions"
                                                        v-model="props.field.$model">
                                    </b-form-radio-group>
                                </template>
                            </b-wrapped-form-group>
                        </b-row>
                    </b-form-fieldset>

                    <b-row>
                        <b-wrapped-form-group class="col-md-6" id="edit_form_disconnect_deactivate_streamer"
                                              :field="form.disconnect_deactivate_streamer" input-type="number"
                                              :input-attrs="{ min: '0' }">
                            <template #label>
                                <translate
                                    key="lang_form_disconnect_deactivate_streamer">Deactivate Streamer on Disconnect (Seconds)</translate>
                            </template>
                            <template #description>
                                <translate key="lang_form_disconnect_deactivate_streamer_desc">This is the number of seconds until a streamer who has been manually disconnected can reconnect to the stream. Set to 0 to allow the streamer to immediately reconnect.</translate>
                            </template>
                        </b-wrapped-form-group>

                        <b-wrapped-form-group class="col-md-6" id="edit_form_backend_dj_port"
                                              :field="form.backend_config.dj_port" input-type="number"
                                              :input-attrs="{ min: '0' }" advanced>
                            <template #label>
                                <translate
                                    key="lang_form_backend_dj_port">Customize DJ/Streamer Port</translate>
                            </template>
                            <template #description>
                                <translate key="lang_form_backend_dj_port_desc">No other program can be using this port. Leave blank to automatically assign a port.</translate>
                                <br>
                                <translate key="lang_form_backend_dj_port_desc_2">Note: the port after this one will automatically be used for legacy connections.</translate>
                            </template>
                        </b-wrapped-form-group>

                        <b-wrapped-form-group class="col-md-6" id="edit_form_backend_dj_buffer"
                                              :field="form.backend_config.dj_buffer" input-type="number"
                                              :input-attrs="{ min: '0', max: '60' }">
                            <template #label>
                                <translate
                                    key="lang_form_backend_dj_buffer">DJ/Streamer Buffer Time (Seconds)</translate>
                            </template>
                            <template #description>
                                <translate key="lang_form_backend_dj_buffer_desc">The number of seconds of signal to store in case of interruption. Set to the lowest value that your DJs can use without stream interruptions.</translate>
                            </template>
                        </b-wrapped-form-group>

                        <b-wrapped-form-group class="col-md-6" id="edit_form_backend_dj_mount_point"
                                              :field="form.backend_config.dj_mount_point" advanced>
                            <template #label>
                                <translate
                                    key="lang_form_backend_dj_mount_point">Customize DJ/Streamer Mount Point</translate>
                            </template>
                            <template #description>
                                <translate key="lang_form_backend_dj_mount_point_desc">If your streaming software requires a specific mount point path, specify it here. Otherwise, use the default.</translate>
                            </template>
                        </b-wrapped-form-group>

                    </b-row>
                </b-form-fieldset>
            </b-form-fieldset>

            <b-form-fieldset>
                <template #label>
                    <translate key="lang_hdr_advanced">Advanced Configuration</translate>
                </template>

                <b-row>
                    <b-wrapped-form-group class="col-md-6" id="edit_form_backend_telnet_port"
                                          :field="form.backend_config.telnet_port" input-type="number"
                                          :input-attrs="{ min: '0' }" advanced>
                        <template #label>
                                <translate
                                    key="lang_form_backend_telnet_port">Customize Internal Request Processing Port</translate>
                        </template>
                        <template #description>
                            <translate key="lang_form_backend_telnet_port_desc">This port is not used by any external process. Only modify this port if the assigned port is in use. Leave blank to automatically assign a port.</translate>
                        </template>
                    </b-wrapped-form-group>

                    <b-wrapped-form-group class="col-md-6"
                                          id="edit_form_backend_enable_replaygain_metadata"
                                          :field="form.backend_config.enable_replaygain_metadata" advanced>
                        <template #description>
                            <translate key="lang_edit_form_backend_enable_replaygain_metadata_desc">Instruct Liquidsoap to use any replaygain metadata associated with a song to control its volume level.</translate>
                        </template>
                        <template #default="props">
                            <b-form-checkbox :id="props.id" v-model="props.field.$model">
                            <translate
                                key="lang_edit_form_backend_enable_replaygain_metadata">Use Replaygain Metadata</translate>
                            </b-form-checkbox>
                        </template>
                    </b-wrapped-form-group>

                    <b-wrapped-form-group class="col-md-6" id="edit_form_backend_autodj_queue_length"
                                          :field="form.backend_config.autodj_queue_length" input-type="number"
                                          :input-attrs="{ min: '0', max: '25' }" advanced>
                        <template #label>
                                <translate
                                    key="lang_form_backend_autodj_queue_length">AutoDJ Queue Length</translate>
                        </template>
                        <template #description>
                            <translate key="lang_form_backend_autodj_queue_length_desc">This determines how many songs in advance the AutoDJ will automatically fill the queue.</translate>
                        </template>
                    </b-wrapped-form-group>

                    <b-wrapped-form-group class="col-md-6"
                                          id="edit_form_backend_use_manual_autodj"
                                          :field="form.backend_config.use_manual_autodj" advanced>
                        <template #description>
                            <translate key="lang_edit_form_backend_use_manual_autodj">This mode disables AzuraCast's AutoDJ management, using Liquidsoap itself to manage song playback. "Next Song" and some other features will not be available.</translate>
                        </template>
                        <template #default="props">
                            <b-form-checkbox :id="props.id" v-model="props.field.$model">
                            <translate
                                key="lang_edit_form_backend_use_manual_autodj">Manual AutoDJ Mode</translate>
                            </b-form-checkbox>
                        </template>
                    </b-wrapped-form-group>

                    <b-wrapped-form-group class="col-md-6" id="edit_form_backend_charset"
                                          :field="form.backend_config.charset" advanced>
                        <template #label>
                            <translate key="lang_form_backend_charset">Character Set Encoding</translate>
                        </template>
                        <template #description>
                            <translate key="lang_form_backend_charset_desc">For most cases, use the default UTF-8 encoding. The older ISO-8859-1 encoding can be used if accepting connections from SHOUTcast 1 DJs or using other legacy software.</translate>
                        </template>
                        <template #default="props">
                            <b-form-radio-group stacked :id="props.id" :options="charsetOptions"
                                                v-model="props.field.$model">
                            </b-form-radio-group>
                        </template>
                    </b-wrapped-form-group>

                    <b-wrapped-form-group class="col-md-6" id="edit_form_backend_duplicate_prevention_time_range"
                                          :field="form.backend_config.duplicate_prevention_time_range"
                                          input-type="number" :input-attrs="{ min: '0', max: '1440' }" advanced>
                        <template #label>
                                <translate
                                    key="lang_form_backend_duplicate_prevention_time_range">Duplicate Prevention Time Range (Minutes)</translate>
                        </template>
                        <template #description>
                            <translate key="lang_form_backend_duplicate_prevention_time_range_desc">This specifies the time range (in minutes) of the song history that the duplicate song prevention algorithm should take into account.</translate>
                        </template>
                    </b-wrapped-form-group>
                </b-row>
            </b-form-fieldset>

        </b-form-fieldset>

    </b-tab>
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
                    text: this.$gettext('Disable Crossfading'),
                    value: 'none',
                }
            ];
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
        }
    }
}
</script>
