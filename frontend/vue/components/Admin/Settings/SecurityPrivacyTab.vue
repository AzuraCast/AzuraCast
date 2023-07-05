<template>
    <form-fieldset>
        <template #label>
            {{ $gettext('Privacy') }}
        </template>

        <div class="row g-3">
            <form-group-multi-check
                id="edit_form_analytics"
                class="col-md-12"
                :field="form.analytics"
                :options="analyticsOptions"
                radio
                stacked
                :label="$gettext('Listener Analytics Collection')"
                :description="$gettext('Aggregate listener statistics are used to show station reports across the system. IP-based listener statistics are used to view live listener tracking and may be required for royalty reports.')"
            >
                <template #label(all)>
                    <b>
                        {{ $gettext('Full:') }}
                    </b>
                    {{ $gettext('Collect aggregate listener statistics and IP-based listener statistics') }}
                </template>
                <template #label(no_ip)>
                    <b>
                        {{ $gettext('Limited:') }}
                    </b>
                    {{ $gettext('Only collect aggregate listener statistics') }}
                </template>
                <template #label(none)>
                    <b>
                        {{ $gettext('None:') }}
                    </b>
                    {{ $gettext('Do not collect any listener analytics') }}
                </template>
            </form-group-multi-check>
        </div>
    </form-fieldset>

    <form-fieldset>
        <template #label>
            {{ $gettext('Security') }}
        </template>

        <div class="row g-3">
            <form-group-checkbox
                id="edit_form_always_use_ssl"
                class="col-md-12"
                :field="form.always_use_ssl"
                :label="$gettext('Always Use HTTPS')"
            >
                <template #description>
                    {{
                        $gettext('Set to "Yes" to always use "https://" secure URLs, and to automatically redirect to the secure URL when an insecure URL is visited.')
                    }}
                </template>
            </form-group-checkbox>

            <form-group-multi-check
                id="edit_form_ip_source"
                class="col-md-6"
                :field="form.ip_source"
                :options="ipSourceOptions"
                stacked
                radio
                :label="$gettext('IP Address Source')"
                :description="$gettext('Customize this setting to ensure you get the correct IP address for remote users. Only change this setting if you use a reverse proxy, either within Docker or a third-party service like CloudFlare.')"
            />

            <form-group-field
                id="edit_form_api_access_control"
                class="col-md-6"
                :field="form.api_access_control"
            >
                <template #label>
                    {{ $gettext('API "Access-Control-Allow-Origin" Header') }}
                </template>
                <template #description>
                    {{
                        $gettext('Set to * to allow all sources, or specify a list of origins separated by a comma (,).')
                    }}
                    <br>
                    <a
                        href="https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Access-Control-Allow-Origin"
                        target="_blank"
                    >
                        {{ $gettext('Learn more about this header.') }}
                    </a>
                </template>
            </form-group-field>
        </div>
    </form-fieldset>
</template>

<script setup>
import FormGroupField from "~/components/Form/FormGroupField.vue";
import FormFieldset from "~/components/Form/FormFieldset";
import FormGroupCheckbox from "~/components/Form/FormGroupCheckbox.vue";
import {useTranslate} from "~/vendor/gettext";
import {computed} from "vue";
import FormGroupMultiCheck from "~/components/Form/FormGroupMultiCheck.vue";

const props = defineProps({
    form: {
        type: Object,
        required: true
    }
});

const {$gettext} = useTranslate();

const analyticsOptions = computed(() => {
    return [
        {
            value: 'all',
            text: 'Full',
        },
        {
            value: 'no_ip',
            text: 'Limited',
        },
        {
            value: 'none',
            text: 'None'
        }
    ]
});

const ipSourceOptions = computed(() => {
    return [
        {
            value: 'local',
            text: $gettext('Local IP (Default)')
        },
        {
            value: 'cloudflare',
            text: $gettext('CloudFlare (CF-Connecting-IP)')
        },
        {
            value: 'xff',
            text: $gettext('Reverse Proxy (X-Forwarded-For)')
        }
    ]
});
</script>
