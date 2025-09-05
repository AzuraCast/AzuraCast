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
                    :field="r$.enable_requests"
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
                    :field="r$.request_delay"
                    input-type="number"
                    :input-attrs="{ min: '0', max: '1440' }"
                    :label="$gettext('Request Minimum Delay (Minutes)')"
                    :description="$gettext('If requests are enabled, this specifies the minimum delay (in minutes) between a request being submitted and being played. If set to zero, a minor delay of 15 seconds is applied to prevent request floods.')"
                />

                <form-group-field
                    id="edit_form_request_threshold"
                    class="col-md-6"
                    :field="r$.request_threshold"
                    input-type="number"
                    :input-attrs="{ min: '0', max: '1440' }"
                    :label="$gettext('Request Last Played Threshold (Minutes)')"
                    :description="$gettext('This specifies the minimum time (in minutes) between a song playing on the radio and being available to request again. Set to 0 for no threshold.')"
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

const tabClass = useFormTabClass(computed(() => r$.value.$groups.requestsTab));

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
