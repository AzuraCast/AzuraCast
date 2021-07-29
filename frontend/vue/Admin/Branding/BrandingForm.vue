<template>
    <form @submit.prevent="submit">
        <section class="card mb-3" role="region">
            <div class="card-header bg-primary-dark">
                <h2 class="card-title">
                    <translate key="lang_header_branding_settings">Branding Settings</translate>
                </h2>
            </div>

            <b-overlay variant="card" :show="loading">
                <div class="card-body">
                    <b-form-group>
                        <b-row>
                            <b-form-group class="col-md-6" label-for="edit_form_public_theme">
                                <template #label>
                                    <translate key="lang_edit_form_public_theme">Base Theme for Public Pages</translate>
                                </template>
                                <template #description>
                                    <translate key="lang_edit_form_public_theme_desc">Select a theme to use as a base for station public pages and the login page.</translate>
                                </template>
                                <b-form-radio-group stacked id="edit_form_public_theme" :options="publicThemeOptions"
                                                    v-model="$v.form.public_theme.$model">
                                </b-form-radio-group>
                            </b-form-group>

                            <b-form-group class="col-md-6" label-for="form_edit_hide_album_art">
                                <template v-slot:description>
                                    <translate key="lang_form_edit_hide_album_art_desc">If selected, album art will not display on public-facing radio pages.</translate>
                                </template>
                                <b-form-checkbox id="form_edit_hide_album_art" v-model="form.hide_album_art.$model">
                                    <translate
                                        key="lang_form_edit_hide_album_art">Hide Album Art on Public Pages</translate>
                                </b-form-checkbox>
                            </b-form-group>
                        </b-row>
                        <b-row>
                            <b-form-group class="col-md-6" label-for="form_edit_homepage_redirect_url">
                                <template #label>
                                    <translate
                                        key="lang_form_edit_homepage_redirect_url">Homepage Redirect URL</translate>
                                </template>
                                <template #description>
                                    <translate key="lang_form_edit_homepage_redirect_url_desc">If a visitor is not signed in and visits the AzuraCast homepage, you can automatically redirect them to the URL specified here. Leave blank to redirect them to the login screen by default.</translate>
                                </template>
                                <b-form-input id="form_edit_homepage_redirect_url" type="text"
                                              v-model="form.homepage_redirect_url.$model"
                                              :state="form.homepage_redirect_url.$dirty ? !form.homepage_redirect_url.$error : null"></b-form-input>
                                <b-form-invalid-feedback>
                                    <translate key="lang_error_required">This field is required.</translate>
                                </b-form-invalid-feedback>
                            </b-form-group>


                            <div class="form-group col-md-6" id="field_default_album_art_url"><label
                                for="azuraforms_form_default_album_art_url" class="">Default Album Art URL </label>
                                <div class="form-field"><input type="text" name="default_album_art_url"
                                                               id="azuraforms_form_default_album_art_url" value=""
                                                               type="text" class=""/></div>
                                <small class="help-block">If a song has no album art, this URL will be listed instead.
                                    Leave
                                    blank to use the standard placeholder art.</small></div>
                            <div class="form-group col-sm-12" id="field_hide_product_name"><label
                                for="azuraforms_form_hide_product_name" class="">Hide AzuraCast Branding on Public
                                Pages </label>
                                <div class="form-field"><input type="hidden" name="hide_product_name" value="0"/><input
                                    type="checkbox" name="hide_product_name" id="azuraforms_form_hide_product_name"
                                    value="1" class="toggle-switch"/><label for="azuraforms_form_hide_product_name">Hide
                                    AzuraCast Branding on Public Pages</label></div>
                                <small class="help-block">If selected, this will remove the AzuraCast branding from
                                    public-facing pages.</small></div>
                            <div class="form-group col-sm-12" id="field_public_custom_css"><label
                                for="azuraforms_form_public_custom_css" class="">Custom CSS for Public Pages </label>
                                <div class="form-field"><textarea name="public_custom_css"
                                                                  id="azuraforms_form_public_custom_css"
                                                                  class=" css-editor"
                                                                  spellcheck="false" type="text" rows="6"
                                                                  cols="60"></textarea></div>
                                <small class="help-block">This CSS will be applied to the station public pages and login
                                    page.</small></div>
                            <div class="form-group col-sm-12" id="field_public_custom_js"><label
                                for="azuraforms_form_public_custom_js" class="">Custom JS for Public Pages </label>
                                <div class="form-field"><textarea name="public_custom_js"
                                                                  id="azuraforms_form_public_custom_js"
                                                                  class=" js-editor"
                                                                  spellcheck="false" type="text" rows="6"
                                                                  cols="60"></textarea></div>
                                <small class="help-block">This javascript code will be applied to the station public
                                    pages
                                    and login page.</small></div>
                            <div class="form-group col-sm-12" id="field_internal_custom_css"><label
                                for="azuraforms_form_internal_custom_css" class="">Custom CSS for Internal
                                Pages </label>
                                <div class="form-field"><textarea name="internal_custom_css"
                                                                  id="azuraforms_form_internal_custom_css"
                                                                  class=" css-editor" spellcheck="false" type="text"
                                                                  rows="6" cols="60"></textarea></div>
                                <small class="help-block">This CSS will be applied to the main management pages, like
                                    this
                                    one.</small></div>
                            <div class="form-group col-sm-12" id="field_submit">
                                <div class="form-field"><input type="submit" name="submit" id="azuraforms_form_submit"
                                                               value="Save Changes" type="submit"
                                                               class=" btn btn-lg btn-primary"/></div>
                            </div>
                    </b-form-group>
                </div>
            </b-overlay>
        </section>
    </form>
</template>

<script>

import {validationMixin} from "vuelidate";

export default {
    name: 'BrandingForm',
    props: {
        apiUrl: String
    },
    mixins: [
        validationMixin
    ],
    data() {
        return {
            loading: true,
            form: {},
        };
    },
    computed: {
        publicThemeOptions() {
            return [
                {
                    name: this.$gettext('Prefer System Default'),
                    value: 'browser',
                },
                {
                    name: this.$gettext('Light'),
                    value: 'light',
                },
                {
                    name: this.$gettext('Dark'),
                    value: 'dark',
                }
            ];
        }
    },
    validations: {
        form: {
            'public_theme': {},
            'hide_album_art': {},
            'homepage_redirect_url': {},
        }
    },
    mounted() {
        this.relist();
    },
    methods: {
        relist() {
            this.loading = true;
        },
        submit() {

        }
    }
}
</script>

