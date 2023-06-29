<template>
    <b-form-fieldset v-if="isBackendEnabled">
        <b-form-fieldset>
            <template #label>
                {{ $gettext('Song Requests') }}
            </template>
            <template #description>
                {{
                    $gettext('Some stream licensing providers may have specific rules regarding song requests. Check your local regulations for more information.')
                }}
            </template>

            <b-form-fieldset>
                <div class="row g-3">
                    <b-wrapped-form-checkbox
                        id="edit_form_enable_requests"
                        class="col-md-12"
                        :field="form.enable_requests"
                    >
                        <template #label>
                            {{ $gettext('Allow Song Requests') }}
                        </template>
                        <template #description>
                            {{
                                $gettext('Enable listeners to request a song for play on your station. Only songs that are already in your playlists are requestable.')
                            }}
                        </template>
                    </b-wrapped-form-checkbox>
                </div>
            </b-form-fieldset>

            <b-form-fieldset v-if="form.enable_requests.$model">
                <div class="row g-3">
                    <b-wrapped-form-group
                        id="edit_form_request_delay"
                        class="col-md-6"
                        :field="form.request_delay"
                        input-type="number"
                        :input-attrs="{ min: '0', max: '1440' }"
                    >
                        <template #label>
                            {{ $gettext('Request Minimum Delay (Minutes)') }}
                        </template>
                        <template #description>
                            {{
                                $gettext('If requests are enabled, this specifies the minimum delay (in minutes) between a request being submitted and being played. If set to zero, a minor delay of 15 seconds is applied to prevent request floods.')
                            }}
                        </template>
                    </b-wrapped-form-group>

                    <b-wrapped-form-group
                        id="edit_form_request_threshold"
                        class="col-md-6"
                        :field="form.request_threshold"
                        input-type="number"
                        :input-attrs="{ min: '0', max: '1440' }"
                    >
                        <template #label>
                            {{ $gettext('Request Last Played Threshold (Minutes)') }}
                        </template>
                        <template #description>
                            {{
                                $gettext('This specifies the minimum time (in minutes) between a song playing on the radio and being available to request again. Set to 0 for no threshold.')
                            }}
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
</script>
