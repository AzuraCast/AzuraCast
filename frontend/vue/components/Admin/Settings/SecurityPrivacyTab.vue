<template>
    <div>
        <b-form-fieldset>
            <template #label>
                {{ $gettext('Privacy') }}
            </template>

            <b-form-row>
                <b-wrapped-form-group class="col-md-12" id="edit_form_analytics" :field="form.analytics">
                    <template #label="{lang}">
                        {{ $gettext('Listener Analytics Collection') }}
                    </template>
                    <template #description="{lang}">
                        {{
                            $gettext('Aggregate listener statistics are used to show station reports across the system. IP-based listener statistics are used to view live listener tracking and may be required for royalty reports.')
                        }}
                    </template>
                    <template #default="props">
                        <b-form-radio-group stacked :id="props.id" v-model="props.field.$model">
                            <b-form-radio value="all">
                                <b>
                                    {{ $gettext('Full:') }}
                                </b>
                                {{ $gettext('Collect aggregate listener statistics and IP-based listener statistics') }}
                            </b-form-radio>
                            <b-form-radio value="no_ip">
                                <b>
                                    {{ $gettext('Limited:') }}
                                </b>
                                {{ $gettext('Only collect aggregate listener statistics') }}
                            </b-form-radio>
                            <b-form-radio value="none">
                                <b>
                                    {{ $gettext('None:') }}
                                </b>
                                {{ $gettext('Do not collect any listener analytics') }}
                            </b-form-radio>
                        </b-form-radio-group>
                    </template>
                </b-wrapped-form-group>
            </b-form-row>
        </b-form-fieldset>

        <b-form-fieldset>
            <template #label>
                {{ $gettext('Security') }}
            </template>

            <b-form-row>
                <b-wrapped-form-checkbox class="col-md-12" id="edit_form_always_use_ssl"
                                         :field="form.always_use_ssl">
                    <template #label="{lang}">
                        {{ $gettext('Always Use HTTPS') }}
                    </template>
                    <template #description="{lang}">
                        {{
                            $gettext('Set to "Yes" to always use "https://" secure URLs, and to automatically redirect to the secure URL when an insecure URL is visited.')
                        }}
                    </template>
                </b-wrapped-form-checkbox>

                <b-wrapped-form-group class="col-md-12" id="edit_form_api_access_control"
                                      :field="form.api_access_control">
                    <template #label="{lang}">
                        {{ $gettext('API "Access-Control-Allow-Origin" Header') }}
                    </template>
                    <template #description="{lang}">
                        {{
                            $gettext('Set to * to allow all sources, or specify a list of origins separated by a comma (,).')
                        }}
                        <br>
                        <a href="https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Access-Control-Allow-Origin"
                           target="_blank">
                            {{ $gettext('Learn more about this header.') }}
                        </a>
                    </template>
                </b-wrapped-form-group>
            </b-form-row>
        </b-form-fieldset>
    </div>
</template>

<script>
import BWrappedFormGroup from "~/components/Form/BWrappedFormGroup";
import BFormFieldset from "~/components/Form/BFormFieldset";
import BWrappedFormCheckbox from "~/components/Form/BWrappedFormCheckbox";

export default {
    name: 'SettingsSecurityPrivacyTab',
    components: {BWrappedFormCheckbox, BFormFieldset, BWrappedFormGroup},
    props: {
        form: Object
    }
}
</script>
