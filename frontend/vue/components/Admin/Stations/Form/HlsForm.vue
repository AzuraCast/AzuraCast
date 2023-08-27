<template>
    <tab
        :label="$gettext('HLS')"
        :item-header-class="tabClassWithBackend"
    >
        <form-fieldset v-if="isBackendEnabled">
            <template #label>
                {{ $gettext('HTTP Live Streaming (HLS)') }}
            </template>
            <template #description>
                {{
                    $gettext('HTTP Live Streaming (HLS) is a new adaptive-bitrate technology supported by some clients. It does not use the standard broadcasting frontends.')
                }}
            </template>

            <div class="row g-3 mb-3">
                <form-group-checkbox
                    id="edit_form_enable_hls"
                    class="col-md-12"
                    :field="v$.enable_hls"
                    :label="$gettext('Enable HTTP Live Streaming (HLS)')"
                />
            </div>

            <div
                v-if="form.enable_hls"
                class="row g-3 mb-3"
            >
                <form-group-checkbox
                    id="edit_form_backend_hls_enable_on_public_player"
                    class="col-md-12"
                    :field="v$.backend_config.hls_enable_on_public_player"
                    :label="$gettext('Show HLS Stream on Public Player')"
                />

                <form-group-checkbox
                    id="edit_form_backend_hls_is_default"
                    class="col-md-12"
                    :field="v$.backend_config.hls_is_default"
                    :label="$gettext('Make HLS Stream Default in Public Player')"
                />
            </div>

            <div
                v-if="enableAdvancedFeatures && form.enable_hls"
                class="row g-3 mb-3"
            >
                <form-group-field
                    id="edit_form_backend_hls_segment_length"
                    class="col-md-4"
                    :field="v$.backend_config.hls_segment_length"
                    input-type="number"
                    :input-attrs="{ min: '0', max: '60' }"
                    advanced
                    :label="$gettext('Segment Length (Seconds)')"
                />

                <form-group-field
                    id="edit_form_backend_hls_segments_in_playlist"
                    class="col-md-4"
                    :field="v$.backend_config.hls_segments_in_playlist"
                    input-type="number"
                    :input-attrs="{ min: '0', max: '60' }"
                    advanced
                    :label="$gettext('Segments in Playlist')"
                />

                <form-group-field
                    id="edit_form_backend_hls_segments_overhead"
                    class="col-md-4"
                    :field="v$.backend_config.hls_segments_overhead"
                    input-type="number"
                    :input-attrs="{ min: '0', max: '60' }"
                    advanced
                    :label="$gettext('Segments Overhead')"
                />
            </div>
        </form-fieldset>
        <backend-disabled v-else />
    </tab>
</template>

<script setup lang="ts">
import FormFieldset from "~/components/Form/FormFieldset.vue";
import FormGroupField from "~/components/Form/FormGroupField.vue";
import {BackendAdapter} from "~/components/Entity/RadioAdapters";
import FormGroupCheckbox from "~/components/Form/FormGroupCheckbox.vue";
import BackendDisabled from "./Common/BackendDisabled.vue";
import {computed} from "vue";
import {useVModel} from "@vueuse/core";
import {useVuelidateOnFormTab} from "~/functions/useVuelidateOnFormTab";
import {numeric} from "@vuelidate/validators";
import {useAzuraCast} from "~/vendor/azuracast";
import Tab from "~/components/Common/Tab.vue";

const props = defineProps({
    form: {
        type: Object,
        required: true
    },
    station: {
        type: Object,
        required: true
    }
});

const {enableAdvancedFeatures} = useAzuraCast();

const emit = defineEmits(['update:form']);
const form = useVModel(props, 'form', emit);

const {v$, tabClass} = useVuelidateOnFormTab(
    computed(() => {
        let validations: {
            [key: string | number]: any
        } = {
            enable_hls: {},
            backend_config: {
                hls_enable_on_public_player: {},
                hls_is_default: {},
            },
        };

        if (enableAdvancedFeatures) {
            validations = {
                ...validations,
                backend_config: {
                    ...validations.backend_config,
                    hls_segment_length: {numeric},
                    hls_segments_in_playlist: {numeric},
                    hls_segments_overhead: {numeric},
                },
            };
        }

        return validations;
    }),
    form,
    () => {
        let blankForm: {
            [key: string | number]: any
        } = {
            enable_hls: false,
            backend_config: {
                hls_enable_on_public_player: false,
                hls_is_default: false,
            }
        };

        if (enableAdvancedFeatures) {
            blankForm = {
                ...blankForm,
                backend_config: {
                    ...blankForm.backend_config,
                    hls_segment_length: 4,
                    hls_segments_in_playlist: 5,
                    hls_segments_overhead: 2,
                }
            };
        }

        return blankForm;
    },
);

const isBackendEnabled = computed(() => {
    return form.value.backend_type !== BackendAdapter.None;
});

const tabClassWithBackend = computed(() => {
    if (tabClass.value) {
        return tabClass.value;
    }

    return (isBackendEnabled.value) ? null : 'text-muted';
});
</script>
