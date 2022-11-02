<template>
    <div>
        <b-form-fieldset>
            <b-form-row>
                <b-wrapped-form-group class="col-md-6" id="form_name" :field="form.name">
                    <template #label="{lang}">
                        <translate :key="lang">Name</translate>
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group class="col-md-6" id="form_email" :field="form.email">
                    <template #label="{lang}">
                        <translate :key="lang">E-mail Address</translate>
                    </template>
                </b-wrapped-form-group>
            </b-form-row>
        </b-form-fieldset>

        <b-form-fieldset>
            <template #label>
                <translate key="lang_hdr_customize">Customization</translate>
            </template>

            <b-form-row>
                <b-col md="6">
                    <b-wrapped-form-group id="edit_form_locale"
                                          :field="form.locale">
                        <template #label="{lang}">
                            <translate :key="lang">Language</translate>
                        </template>
                        <template #default="props">
                            <b-form-radio-group stacked :id="props.id" :options="localeOptions"
                                                v-model="props.field.$model">
                            </b-form-radio-group>
                        </template>
                    </b-wrapped-form-group>
                </b-col>
                <b-col md="6">
                    <b-wrapped-form-group id="edit_form_theme"
                                          :field="form.theme">
                        <template #label="{lang}">
                            <translate :key="lang">Site Theme</translate>
                        </template>
                        <template #default="props">
                            <b-form-radio-group stacked :id="props.id" :options="themeOptions"
                                                v-model="props.field.$model">
                            </b-form-radio-group>
                        </template>
                    </b-wrapped-form-group>

                    <b-wrapped-form-group id="edit_form_show_24_hour_time"
                                          :field="form.show_24_hour_time">
                        <template #label="{lang}">
                            <translate :key="lang">Time Display</translate>
                        </template>
                        <template #default="props">
                            <b-form-radio-group stacked :id="props.id" :options="show24hourOptions"
                                                v-model="props.field.$model">
                            </b-form-radio-group>
                        </template>
                    </b-wrapped-form-group>
                </b-col>
            </b-form-row>
        </b-form-fieldset>
    </div>
</template>

<script>
import BWrappedFormGroup from "~/components/Form/BWrappedFormGroup";
import BFormFieldset from "~/components/Form/BFormFieldset";
import objectToFormOptions from "~/functions/objectToFormOptions";

export default {
    name: 'AccountEditForm',
    props: {
        form: Object,
        supportedLocales: Object
    },
    components: {BFormFieldset, BWrappedFormGroup},
    computed: {
        localeOptions() {
            let localeOptions = objectToFormOptions(this.supportedLocales);
            localeOptions.unshift({
                text: this.$gettext('Use Browser Default'),
                value: 'default'
            });
            return localeOptions;
        },
        themeOptions() {
            return [
                {
                    text: this.$gettext('Prefer System Default'),
                    value: 'browser'
                },
                {
                    text: this.$gettext('Light'),
                    value: 'light'
                },
                {
                    text: this.$gettext('Dark'),
                    value: 'dark'
                }
            ];
        },
        show24hourOptions() {
            return [
                {
                    text: this.$gettext('Prefer System Default'),
                    value: null
                },
                {
                    text: this.$gettext('12 Hour'),
                    value: false
                },
                {
                    text: this.$gettext('24 Hour'),
                    value: true
                }
            ];
        }
    }
}
</script>
