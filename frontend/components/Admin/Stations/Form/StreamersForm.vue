<template>
    <tab
        :label="$gettext('Streamers/DJs')"
        :item-header-class="tabClassWithBackend"
    >
        <form-fieldset v-if="isBackendEnabled">
            <template #label>
                {{ $gettext('Streamers/DJs') }}
            </template>

            <div class="row g-3 mb-3">
                <form-group-checkbox
                    id="edit_form_enable_streamers"
                    class="col-md-12"
                    :field="r$.enable_streamers"
                    :label="$gettext('Allow Streamers / DJs')"
                    :description="$gettext('If enabled, streamers (or DJs) will be able to connect directly to your stream and broadcast live music that interrupts the AutoDJ stream.')"
                />
            </div>

            <template v-if="form.enable_streamers">
                <div class="row g-3 mb-3">
                    <form-group-checkbox
                        id="edit_form_backend_record_streams"
                        class="col-md-12"
                        :field="r$.backend_config.record_streams"
                        :label="$gettext('Record Live Broadcasts')"
                        :description="$gettext('If enabled, AzuraCast will automatically record any live broadcasts made to this station to per-broadcast recordings.')"
                    />
                </div>
                <div
                    v-if="form.backend_config.record_streams"
                    class="row g-3 mb-3"
                >
                    <form-group-multi-check
                        id="edit_form_backend_record_streams_format"
                        class="col-md-6"
                        :field="r$.backend_config.record_streams_format"
                        :options="recordStreamsOptions"
                        stacked
                        radio
                        :label="$gettext('Live Broadcast Recording Format')"
                    />

                    <form-group-field
                        id="edit_form_backend_record_streams_bitrate"
                        class="col-md-6"
                        :field="r$.backend_config.record_streams_bitrate"
                        :label="$gettext('Live Broadcast Recording Bitrate (kbps)')"
                    >
                        <template #default="{id, model, fieldClass, inputAttrs}">
                            <bitrate-options
                                :id="id"
                                v-model="model.$model"
                                :class="fieldClass"
                                :input-attrs="inputAttrs"
                                :max-bitrate="form.max_bitrate"
                            />
                        </template>
                    </form-group-field>
                </div>

                <div class="row g-3 mb-3">
                    <form-group-field
                        id="edit_form_disconnect_deactivate_streamer"
                        class="col-md-6"
                        :field="r$.disconnect_deactivate_streamer"
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
                    </form-group-field>

                    <form-group-field
                        id="edit_form_backend_dj_port"
                        class="col-md-6"
                        :field="r$.backend_config.dj_port"
                        input-type="number"
                        :input-attrs="{ min: '0' }"
                        advanced
                        :label="$gettext('Customize DJ/Streamer Port')"
                    >
                        <template #description>
                            {{
                                $gettext('No other program can be using this port. Leave blank to automatically assign a port.')
                            }}
                            <br>
                            {{
                                $gettext('Note: the port after this one will automatically be used for legacy connections.')
                            }}
                        </template>
                    </form-group-field>

                    <form-group-field
                        id="edit_form_backend_dj_buffer"
                        class="col-md-6"
                        :field="r$.backend_config.dj_buffer"
                        input-type="number"
                        :input-attrs="{ min: '0', max: '60' }"
                        :label="$gettext('DJ/Streamer Buffer Time (Seconds)')"
                        :description="$gettext('The number of seconds of signal to store in case of interruption. Set to the lowest value that your DJs can use without stream interruptions.')"
                    />

                    <form-group-field
                        id="edit_form_backend_dj_mount_point"
                        class="col-md-6"
                        :field="r$.backend_config.dj_mount_point"
                        advanced
                        :label="$gettext('Customize DJ/Streamer Mount Point')"
                        :description="$gettext('If your streaming software requires a specific mount point path, specify it here. Otherwise, use the default.')"
                    />

                    <form-group-field
                        id="edit_form_backend_live_broadcast_text"
                        class="col-md-6"
                        :field="r$.backend_config.live_broadcast_text"
                        :label="$gettext('Default Live Broadcast Message')"
                        :description="$gettext('If a live DJ connects but has not yet sent metadata, this is the message that will display on player pages.')"
                    />
                </div>
            </template>
        </form-fieldset>
        <backend-disabled v-else />
    </tab>
</template>

<script setup lang="ts">
import FormFieldset from "~/components/Form/FormFieldset.vue";
import FormGroupField from "~/components/Form/FormGroupField.vue";
import FormGroupCheckbox from "~/components/Form/FormGroupCheckbox.vue";
import BackendDisabled from "~/components/Admin/Stations/Form/Common/BackendDisabled.vue";
import {computed} from "vue";
import FormGroupMultiCheck from "~/components/Form/FormGroupMultiCheck.vue";
import Tab from "~/components/Common/Tab.vue";
import BitrateOptions from "~/components/Common/BitrateOptions.vue";
import {BackendAdapters, StreamFormats} from "~/entities/ApiInterfaces.ts";
import {storeToRefs} from "pinia";
import {useAdminStationsForm} from "~/components/Admin/Stations/Form/form.ts";
import {useFormTabClass} from "~/functions/useFormTabClass.ts";

const {r$, form} = storeToRefs(useAdminStationsForm());

const tabClass = useFormTabClass(computed(() => r$.value.$groups.streamersTab));

const isBackendEnabled = computed(() => {
    return form.value.backend_type !== BackendAdapters.None;
});

const tabClassWithBackend = computed(() => {
    if (tabClass.value) {
        return tabClass.value;
    }

    return (isBackendEnabled.value) ? '' : 'text-muted';
});

const recordStreamsOptions = computed(() => {
    return [
        {
            text: 'MP3',
            value: StreamFormats.Mp3
        },
        {
            text: 'OGG Vorbis',
            value: StreamFormats.Ogg
        },
        {
            text: 'OGG Opus',
            value: StreamFormats.Opus
        },
        {
            text: 'AAC+ (MPEG4 HE-AAC v2)',
            value: StreamFormats.Aac
        }
    ];
});
</script>
