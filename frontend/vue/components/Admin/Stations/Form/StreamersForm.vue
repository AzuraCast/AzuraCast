<template>
    <b-form-fieldset v-if="isBackendEnabled">
        <b-form-fieldset>
            <template #label>
                {{ $gettext('Streamers/DJs') }}
            </template>

            <b-form-fieldset>
                <div class="form-row">
                    <b-wrapped-form-checkbox
                        id="edit_form_enable_streamers"
                        class="col-md-12"
                        :field="form.enable_streamers"
                    >
                        <template #label>
                            {{ $gettext('Allow Streamers / DJs') }}
                        </template>
                        <template #description>
                            {{
                                $gettext('If enabled, streamers (or DJs) will be able to connect directly to your stream and broadcast live music that interrupts the AutoDJ stream.')
                            }}
                        </template>
                    </b-wrapped-form-checkbox>
                </div>
            </b-form-fieldset>

            <b-form-fieldset v-if="form.enable_streamers.$model">
                <b-form-fieldset>
                    <div class="form-row">
                        <b-wrapped-form-checkbox
                            id="edit_form_backend_record_streams"
                            class="col-md-12"
                            :field="form.backend_config.record_streams"
                        >
                            <template #label>
                                {{ $gettext('Record Live Broadcasts') }}
                            </template>
                            <template #description>
                                {{
                                    $gettext('If enabled, AzuraCast will automatically record any live broadcasts made to this station to per-broadcast recordings.')
                                }}
                            </template>
                        </b-wrapped-form-checkbox>
                    </div>
                </b-form-fieldset>

                <b-form-fieldset v-if="form.backend_config.record_streams.$model">
                    <div class="form-row">
                        <b-wrapped-form-group
                            id="edit_form_backend_record_streams_format"
                            class="col-md-6"
                            :field="form.backend_config.record_streams_format"
                        >
                            <template #label>
                                {{ $gettext('Live Broadcast Recording Format') }}
                            </template>

                            <template #default="slotProps">
                                <b-form-radio-group
                                    :id="slotProps.id"
                                    v-model="slotProps.field.$model"
                                    stacked
                                    :options="recordStreamsOptions"
                                />
                            </template>
                        </b-wrapped-form-group>

                        <b-wrapped-form-group
                            id="edit_form_backend_record_streams_bitrate"
                            class="col-md-6"
                            :field="form.backend_config.record_streams_bitrate"
                        >
                            <template #label>
                                {{ $gettext('Live Broadcast Recording Bitrate (kbps)') }}
                            </template>

                            <template #default="slotProps">
                                <b-form-radio-group
                                    :id="slotProps.id"
                                    v-model="slotProps.field.$model"
                                    stacked
                                    :options="recordBitrateOptions"
                                />
                            </template>
                        </b-wrapped-form-group>
                    </div>
                </b-form-fieldset>

                <b-form-fieldset>
                    <div class="form-row">
                        <b-wrapped-form-group
                            id="edit_form_disconnect_deactivate_streamer"
                            class="col-md-6"
                            :field="form.disconnect_deactivate_streamer"
                            input-type="number"
                            :input-attrs="{ min: '0' }"
                        >
                            <template #label>
                                {{ $gettext('Deactivate Streamer on Disconnect (Seconds)') }}
                            </template>
                            <template #description>
                                {{
                                    $gettext('This is the number of seconds until a streamer who has been manually disconnected can reconnect to the stream. Set to 0 to allow the streamer to immediately reconnect.')
                                }}
                            </template>
                        </b-wrapped-form-group>

                        <b-wrapped-form-group
                            v-if="showAdvanced"
                            id="edit_form_backend_dj_port"
                            class="col-md-6"
                            :field="form.backend_config.dj_port"
                            input-type="number"
                            :input-attrs="{ min: '0' }"
                            advanced
                        >
                            <template #label>
                                {{ $gettext('Customize DJ/Streamer Port') }}
                            </template>
                            <template #description>
                                {{
                                    $gettext('No other program can be using this port. Leave blank to automatically assign a port.')
                                }}
                                <br>
                                {{
                                    $gettext('Note: the port after this one will automatically be used for legacy connections.')
                                }}
                            </template>
                        </b-wrapped-form-group>

                        <b-wrapped-form-group
                            id="edit_form_backend_dj_buffer"
                            class="col-md-6"
                            :field="form.backend_config.dj_buffer"
                            input-type="number"
                            :input-attrs="{ min: '0', max: '60' }"
                        >
                            <template #label>
                                {{ $gettext('DJ/Streamer Buffer Time (Seconds)') }}
                            </template>
                            <template #description>
                                {{
                                    $gettext('The number of seconds of signal to store in case of interruption. Set to the lowest value that your DJs can use without stream interruptions.')
                                }}
                            </template>
                        </b-wrapped-form-group>

                        <b-wrapped-form-group
                            v-if="showAdvanced"
                            id="edit_form_backend_dj_mount_point"
                            class="col-md-6"
                            :field="form.backend_config.dj_mount_point"
                            advanced
                        >
                            <template #label>
                                {{ $gettext('Customize DJ/Streamer Mount Point') }}
                            </template>
                            <template #description>
                                {{
                                    $gettext('If your streaming software requires a specific mount point path, specify it here. Otherwise, use the default.')
                                }}
                            </template>
                        </b-wrapped-form-group>
                    </div>
                </b-form-fieldset>
            </b-form-fieldset>
        </b-form-fieldset>
    </b-form-fieldset>
    <backend-disabled v-else />
</template>

<script setup>
import BFormFieldset from "~/components/Form/BFormFieldset.vue";
import BWrappedFormGroup from "~/components/Form/BWrappedFormGroup.vue";
import {BACKEND_NONE} from "~/components/Entity/RadioAdapters";
import BWrappedFormCheckbox from "~/components/Form/BWrappedFormCheckbox.vue";
import BackendDisabled from "./Common/BackendDisabled.vue";
import {computed} from "vue";

const props = defineProps({
    form: {
        type: Object,
        required: true
    },
    station: {
        type: Object,
        required: true
    },
    showAdvanced: {
        type: Boolean,
        default: true
    },
});

const isBackendEnabled = computed(() => {
    return props.form.backend_type.$model !== BACKEND_NONE;
});

const recordStreamsOptions = computed(() => {
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
});

const recordBitrateOptions = computed(() => {
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
});
</script>
