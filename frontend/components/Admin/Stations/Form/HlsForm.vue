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
                    :field="r$.enable_hls"
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
                    :field="r$.backend_config.hls_enable_on_public_player"
                    :label="$gettext('Show HLS Stream on Public Player')"
                />

                <form-group-checkbox
                    id="edit_form_backend_hls_is_default"
                    class="col-md-12"
                    :field="r$.backend_config.hls_is_default"
                    :label="$gettext('Make HLS Stream Default in Public Player')"
                />
            </div>

            <div
                v-if="form.enable_hls"
                class="row g-3 mb-3"
            >
                <form-group-field
                    id="edit_form_backend_hls_segment_length"
                    class="col-md-4"
                    :field="r$.backend_config.hls_segment_length"
                    input-type="number"
                    :input-attrs="{ min: '0', max: '9999' }"
                    advanced
                    :label="$gettext('Segment Length (Seconds)')"
                />

                <form-group-field
                    id="edit_form_backend_hls_segments_in_playlist"
                    class="col-md-4"
                    :field="r$.backend_config.hls_segments_in_playlist"
                    input-type="number"
                    :input-attrs="{ min: '0', max: '60' }"
                    advanced
                    :label="$gettext('Segments in Playlist')"
                />

                <form-group-field
                    id="edit_form_backend_hls_segments_overhead"
                    class="col-md-4"
                    :field="r$.backend_config.hls_segments_overhead"
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
import FormGroupCheckbox from "~/components/Form/FormGroupCheckbox.vue";
import BackendDisabled from "~/components/Admin/Stations/Form/Common/BackendDisabled.vue";
import {computed} from "vue";
import Tab from "~/components/Common/Tab.vue";
import {BackendAdapters} from "~/entities/ApiInterfaces.ts";
import {storeToRefs} from "pinia";
import {useAdminStationsForm} from "~/components/Admin/Stations/Form/form.ts";
import {useFormTabClass} from "~/functions/useFormTabClass.ts";

const {r$, form} = storeToRefs(useAdminStationsForm());

const tabClass = useFormTabClass(computed(() => r$.value.$groups.hlsTab));

const isBackendEnabled = computed(() => {
    return form.value.backend_type !== BackendAdapters.None;
});

const tabClassWithBackend = computed(() => {
    if (tabClass.value) {
        return tabClass.value;
    }

    return (isBackendEnabled.value) ? '' : 'text-muted';
});
</script>
