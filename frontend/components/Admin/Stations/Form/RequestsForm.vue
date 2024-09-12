<template>
    <tab
        :label="$gettext('Song Requests')"
        :item-header-class="tabClassWithBackend"
    >
        <form-fieldset v-if="isBackendEnabled">
            <template #label>
                {{ $gettext('Song Requests') }}
            </template>
            <template #description>
                {{
                    $gettext('Some stream licensing providers may have specific rules regarding song requests. Check your local regulations for more information.')
                }}
            </template>

            <div class="row g-3 mb-3">
                <form-group-checkbox
                    id="edit_form_enable_requests"
                    class="col-md-12"
                    :field="v$.enable_requests"
                    :label="$gettext('Allow Song Requests')"
                    :description="$gettext('Enable listeners to request a song for play on your station. Only songs that are already in your playlists are requestable.')"
                />
            </div>

            <div
                v-if="form.enable_requests"
                class="row g-3 mb-3"
            >
                <form-group-field
                    id="edit_form_request_delay"
                    class="col-md-6"
                    :field="v$.request_delay"
                    input-type="number"
                    :input-attrs="{ min: '0', max: '1440' }"
                    :label="$gettext('Request Minimum Delay (Minutes)')"
                    :description="$gettext('If requests are enabled, this specifies the minimum delay (in minutes) between a request being submitted and being played. If set to zero, a minor delay of 15 seconds is applied to prevent request floods.')"
                />

                <form-group-field
                    id="edit_form_request_threshold"
                    class="col-md-6"
                    :field="v$.request_threshold"
                    input-type="number"
                    :input-attrs="{ min: '0', max: '1440' }"
                    :label="$gettext('Request Last Played Threshold (Minutes)')"
                    :description="$gettext('This specifies the minimum time (in minutes) between a song playing on the radio and being available to request again. Set to 0 for no threshold.')"
                />

                <form-group-field
                    id="form_edit_request_priority"
                    class="col-md-6"
                    :field="v$.request_priority"
                    input-type="number"
                    :input-attrs="{min: '0', max: '150'}"
                    clearable
                    :label="$gettext('Request Priority')"
                    :description="$gettext('If using playlist priorities, this specifies the priority of incoming requests. Leave blank to put requests at the highest priority.')"
                />

                <form-group-checkbox
                    id="form_edit_requests_follow_format"
                    class="col-md-6"
                    :field="v$.requests_follow_format"
                    :label="$gettext('Force Requests to Follow Playlist Rules')"
                    :description="$gettext('Enable this setting to only queue incoming requests once the playlist they belong to would normally play; if disabled, requests can play regardless of the playback rules of the playlist they belong to.')"
                />
            </div>
        </form-fieldset>
        <backend-disabled v-else />
    </tab>
</template>

<script setup lang="ts">
import FormFieldset from "~/components/Form/FormFieldset.vue";
import FormGroupField from "~/components/Form/FormGroupField.vue";
import {BackendAdapter} from "~/entities/RadioAdapters";
import FormGroupCheckbox from "~/components/Form/FormGroupCheckbox.vue";
import BackendDisabled from "./Common/BackendDisabled.vue";
import {computed} from "vue";
import {useVModel} from "@vueuse/core";
import {useVuelidateOnFormTab} from "~/functions/useVuelidateOnFormTab";
import {numeric} from "@vuelidate/validators";
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

const emit = defineEmits(['update:form']);
const form = useVModel(props, 'form', emit);

const {v$, tabClass} = useVuelidateOnFormTab(
    {
        enable_requests: {},
        request_delay: {numeric},
        request_threshold: {numeric},
        request_priority: {numeric},
        requests_follow_format: {}
    },
    form,
    {
        enable_requests: false,
        request_delay: 5,
        request_threshold: 15,
        request_priority: null,
        requests_follow_format: false
    }
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
