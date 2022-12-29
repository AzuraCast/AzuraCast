<template>
    <b-form-fieldset v-if="isBackendEnabled">
        <b-form-fieldset>
            <template #label>
                {{ $gettext('HTTP Live Streaming (HLS)') }}
            </template>
            <template #description>
                {{
                    $gettext('HTTP Live Streaming (HLS) is a new adaptive-bitrate technology supported by some clients. It does not use the standard broadcasting frontends.')
                }}
            </template>

            <b-form-fieldset>
                <div class="form-row">
                    <b-wrapped-form-checkbox
                        id="edit_form_enable_hls"
                        class="col-md-12"
                        :field="form.enable_hls"
                    >
                        <template #label>
                            {{ $gettext('Enable HTTP Live Streaming (HLS)') }}
                        </template>
                    </b-wrapped-form-checkbox>
                </div>
            </b-form-fieldset>

            <b-form-fieldset v-if="form.enable_hls.$model">
                <div class="form-row">
                    <b-wrapped-form-checkbox
                        id="edit_form_backend_hls_enable_on_public_player"
                        class="col-md-12"
                        :field="form.backend_config.hls_enable_on_public_player"
                    >
                        <template #label>
                            {{ $gettext('Show HLS Stream on Public Player') }}
                        </template>
                    </b-wrapped-form-checkbox>

                    <b-wrapped-form-checkbox
                        id="edit_form_backend_hls_is_default"
                        class="col-md-12"
                        :field="form.backend_config.hls_is_default"
                    >
                        <template #label>
                            {{ $gettext('Make HLS Stream Default in Public Player') }}
                        </template>
                    </b-wrapped-form-checkbox>
                </div>
            </b-form-fieldset>

            <b-form-fieldset v-if="showAdvanced && form.enable_hls.$model">
                <div class="form-row">
                    <b-wrapped-form-group
                        id="edit_form_backend_hls_segment_length"
                        class="col-md-4"
                        :field="form.backend_config.hls_segment_length"
                        input-type="number"
                        :input-attrs="{ min: '0', max: '60' }"
                        advanced
                    >
                        <template #label>
                            {{ $gettext('Segment Length (Seconds)') }}
                        </template>
                    </b-wrapped-form-group>

                    <b-wrapped-form-group
                        id="edit_form_backend_hls_segments_in_playlist"
                        class="col-md-4"
                        :field="form.backend_config.hls_segments_in_playlist"
                        input-type="number"
                        :input-attrs="{ min: '0', max: '60' }"
                        advanced
                    >
                        <template #label>
                            {{ $gettext('Segments in Playlist') }}
                        </template>
                    </b-wrapped-form-group>

                    <b-wrapped-form-group
                        id="edit_form_backend_hls_segments_overhead"
                        class="col-md-4"
                        :field="form.backend_config.hls_segments_overhead"
                        input-type="number"
                        :input-attrs="{ min: '0', max: '60' }"
                        advanced
                    >
                        <template #label>
                            {{ $gettext('Segments Overhead') }}
                        </template>
                    </b-wrapped-form-group>
                </div>
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
  form: Object,
  station: Object,
  showAdvanced: {
    type: Boolean,
        default: true
    },
});

const isBackendEnabled = computed(() => {
    return props.form.backend_type.$model !== BACKEND_NONE;
});
</script>
