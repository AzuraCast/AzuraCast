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
                <b-wrapped-form-group class="col-md-6" id="edit_form_locale"
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

                <b-wrapped-form-group class="col-md-6" id="edit_form_theme"
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
        }
    }
}
</script>
