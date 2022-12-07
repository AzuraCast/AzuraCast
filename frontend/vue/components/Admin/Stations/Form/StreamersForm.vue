<template>
    <div>
        <b-form-fieldset v-if="isBackendEnabled">
            <b-form-fieldset>
                <template #label>
                    <translate key="lang_hdr_streamers">Streamers/DJs</translate>
                </template>

                <b-form-fieldset>
                    <b-form-row>
                        <b-wrapped-form-checkbox class="col-md-12" id="edit_form_enable_streamers"
                                                 :field="form.enable_streamers">
                            <template #label="{lang}">
                                <translate :key="lang">Allow Streamers / DJs</translate>
                            </template>
                            <template #description="{lang}">
                                <translate :key="lang">If enabled, streamers (or DJs) will be able to connect directly
                                    to your stream and broadcast live music that interrupts the AutoDJ stream.
                                </translate>
                            </template>
                        </b-wrapped-form-checkbox>
                    </b-form-row>
                </b-form-fieldset>

                <b-form-fieldset v-if="form.enable_streamers.$model">
                    <b-form-fieldset>
                        <b-form-row>
                            <b-wrapped-form-checkbox class="col-md-12" id="edit_form_backend_record_streams"
                                                     :field="form.backend_config.record_streams">
                                <template #label="{lang}">
                                    <translate :key="lang">Record Live Broadcasts</translate>
                                </template>
                                <template #description="{lang}">
                                    <translate :key="lang">If enabled, AzuraCast will automatically record any live
                                        broadcasts made to this station to per-broadcast recordings.
                                    </translate>
                                </template>
                            </b-wrapped-form-checkbox>
                        </b-form-row>
                    </b-form-fieldset>

                    <b-form-fieldset v-if="form.backend_config.record_streams.$model">
                        <b-form-row>
                            <b-wrapped-form-group class="col-md-6" id="edit_form_backend_record_streams_format"
                                                  :field="form.backend_config.record_streams_format">
                                <template #label="{lang}">
                                    <translate :key="lang">Live Broadcast Recording Format</translate>
                                </template>

                                <template #default="props">
                                    <b-form-radio-group stacked :id="props.id" :options="recordStreamsOptions"
                                                        v-model="props.field.$model">
                                    </b-form-radio-group>
                                </template>
                            </b-wrapped-form-group>

                            <b-wrapped-form-group class="col-md-6" id="edit_form_backend_record_streams_bitrate"
                                                  :field="form.backend_config.record_streams_bitrate">
                                <template #label="{lang}">
                                    <translate :key="lang">Live Broadcast Recording Bitrate (kbps)</translate>
                                </template>

                                <template #default="props">
                                    <b-form-radio-group stacked :id="props.id" :options="recordBitrateOptions"
                                                        v-model="props.field.$model">
                                    </b-form-radio-group>
                                </template>
                            </b-wrapped-form-group>
                        </b-form-row>
                    </b-form-fieldset>

                    <b-form-fieldset>
                        <b-form-row>
                            <b-wrapped-form-group class="col-md-6" id="edit_form_disconnect_deactivate_streamer"
                                                  :field="form.disconnect_deactivate_streamer" input-type="number"
                                                  :input-attrs="{ min: '0' }">
                                <template #label="{lang}">
                                    <translate :key="lang">Deactivate Streamer on Disconnect (Seconds)</translate>
                                </template>
                                <template #description="{lang}">
                                    <translate :key="lang">This is the number of seconds until a streamer who has been
                                        manually disconnected can reconnect to the stream. Set to 0 to allow the
                                        streamer to immediately reconnect.
                                    </translate>
                                </template>
                            </b-wrapped-form-group>

                            <b-wrapped-form-group v-if="showAdvanced" class="col-md-6"
                                                  id="edit_form_backend_dj_port"
                                                  :field="form.backend_config.dj_port" input-type="number"
                                                  :input-attrs="{ min: '0' }" advanced>
                                <template #label="{lang}">
                                    <translate :key="lang">Customize DJ/Streamer Port</translate>
                                </template>
                                <template #description="{lang}">
                                    <translate :key="lang">No other program can be using this port. Leave blank to
                                        automatically assign a port.
                                    </translate>
                                    <br>
                                    <translate :key="lang+'2'">Note: the port after this one will automatically be used
                                        for legacy connections.
                                    </translate>
                                </template>
                            </b-wrapped-form-group>

                            <b-wrapped-form-group class="col-md-6" id="edit_form_backend_dj_buffer"
                                                  :field="form.backend_config.dj_buffer" input-type="number"
                                                  :input-attrs="{ min: '0', max: '60' }">
                                <template #label="{lang}">
                                    <translate :key="lang">DJ/Streamer Buffer Time (Seconds)</translate>
                                </template>
                                <template #description="{lang}">
                                    <translate :key="lang">The number of seconds of signal to store in case of
                                        interruption. Set to the lowest value that your DJs can use without stream
                                        interruptions.
                                    </translate>
                                </template>
                            </b-wrapped-form-group>

                            <b-wrapped-form-group v-if="showAdvanced" class="col-md-6"
                                                  id="edit_form_backend_dj_mount_point"
                                                  :field="form.backend_config.dj_mount_point" advanced>
                                <template #label="{lang}">
                                    <translate :key="lang">Customize DJ/Streamer Mount Point</translate>
                                </template>
                                <template #description="{lang}">
                                    <translate :key="lang">If your streaming software requires a specific mount point
                                        path, specify it here. Otherwise, use the default.
                                    </translate>
                                </template>
                            </b-wrapped-form-group>
                        </b-form-row>
                    </b-form-fieldset>
                </b-form-fieldset>
            </b-form-fieldset>
        </b-form-fieldset>
        <backend-disabled v-else></backend-disabled>
    </div>
</template>

<script>
import BFormFieldset from "~/components/Form/BFormFieldset";
import BWrappedFormGroup from "~/components/Form/BWrappedFormGroup";
import {BACKEND_NONE} from "~/components/Entity/RadioAdapters";
import BWrappedFormCheckbox from "~/components/Form/BWrappedFormCheckbox";
import BFormMarkup from "~/components/Form/BFormMarkup";
import BackendDisabled from "./Common/BackendDisabled.vue";

export default {
    name: 'AdminStationsStreamersForm',
    components: {BackendDisabled, BFormMarkup, BWrappedFormCheckbox, BWrappedFormGroup, BFormFieldset},
    props: {
        form: Object,
        station: Object,
        showAdvanced: {
            type: Boolean,
            default: true
        },
    },
    computed: {
        isBackendEnabled() {
            return this.form.backend_type.$model !== BACKEND_NONE;
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
    }
}
</script>
