<template>
    <form @submit.prevent="submit">
        <section class="card mb-3" role="region">
            <div class="card-header bg-primary-dark">
                <h2 class="card-title">
                    <translate key="lang_header_branding_settings">Branding Settings</translate>
                </h2>
            </div>

            <b-alert variant="danger" :show="error != null">{{ error }}</b-alert>

            <b-overlay variant="card" :show="loading">
                <div class="card-body">
                    <b-form-group>
                        <b-row>
                            <b-wrapped-form-group class="col-md-6" id="edit_form_public_theme"
                                                  :field="$v.form.public_theme">
                                <template #label>
                                    <translate key="lang_edit_form_public_theme">Base Theme for Public Pages</translate>
                                </template>
                                <template #description>
                                    <translate key="lang_edit_form_public_theme_desc">Select a theme to use as a base for station public pages and the login page.</translate>
                                </template>
                                <template #default="props">
                                    <b-form-radio-group stacked :id="props.id" :options="publicThemeOptions"
                                                        v-model="props.field.$model">
                                    </b-form-radio-group>
                                </template>
                            </b-wrapped-form-group>

                            <b-col md="6">
                                <b-wrapped-form-group class="mb-2" id="form_edit_hide_album_art"
                                                      :field="$v.form.hide_album_art">
                                    <template #description>
                                        <translate key="lang_form_edit_hide_album_art_desc">If selected, album art will not display on public-facing radio pages.</translate>
                                    </template>
                                    <template #default="props">
                                        <b-form-checkbox :id="props.id" v-model="props.field.$model">
                                            <translate key="lang_form_edit_hide_album_art">Hide Album Art on Public Pages</translate>
                                        </b-form-checkbox>
                                    </template>
                                </b-wrapped-form-group>

                                <b-wrapped-form-group id="form_edit_hide_product_name"
                                                      :field="$v.form.hide_product_name">
                                    <template #description>
                                        <translate key="lang_form_edit_hide_product_name_desc">If selected, this will remove the AzuraCast branding from public-facing pages.</translate>
                                    </template>
                                    <template #default="props">
                                        <b-form-checkbox :id="props.id" v-model="props.field.$model">
                                            <translate key="lang_form_edit_hide_product_name">Hide AzuraCast Branding on Public Pages</translate>
                                        </b-form-checkbox>
                                    </template>
                                </b-wrapped-form-group>
                            </b-col>

                            <b-wrapped-form-group class="col-md-6" id="form_edit_homepage_redirect_url"
                                                  :field="$v.form.homepage_redirect_url">
                                <template #label>
                                    <translate
                                        key="lang_form_edit_homepage_redirect_url">Homepage Redirect URL</translate>
                                </template>
                                <template #description>
                                    <translate key="lang_form_edit_homepage_redirect_url_desc">If a visitor is not signed in and visits the AzuraCast homepage, you can automatically redirect them to the URL specified here. Leave blank to redirect them to the login screen by default.</translate>
                                </template>
                            </b-wrapped-form-group>

                            <b-wrapped-form-group class="col-md-6" id="form_edit_default_album_art_url"
                                                  :field="$v.form.default_album_art_url">
                                <template #label>
                                    <translate
                                        key="lang_form_edit_default_album_art_url">Default Album Art URL</translate>
                                </template>
                                <template #description>
                                    <translate key="lang_form_edit_default_album_art_url_desc">If a song has no album art, this URL will be listed instead. Leave blank to use the standard placeholder art.</translate>
                                </template>
                            </b-wrapped-form-group>

                            <b-wrapped-form-group class="col-md-12" id="edit_form_public_custom_css"
                                                  :field="$v.form.public_custom_css">
                                <template #label>
                                    <translate
                                        key="lang_edit_form_public_custom_css">Custom CSS for Public Pages</translate>
                                </template>
                                <template #description>
                                    <translate key="lang_edit_form_public_custom_css_desc">This CSS will be applied to the station public pages and login page.</translate>
                                </template>
                                <template #default="props">
                                    <codemirror-textarea :id="props.id" mode="css"
                                                         v-model="props.field.$model"></codemirror-textarea>
                                </template>
                            </b-wrapped-form-group>

                            <b-wrapped-form-group class="col-md-12" id="edit_form_public_custom_js"
                                                  :field="$v.form.public_custom_js">
                                <template #label>
                                    <translate
                                        key="lang_edit_form_public_custom_js">Custom JS for Public Pages</translate>
                                </template>
                                <template #description>
                                    <translate key="lang_edit_form_public_custom_js_desc">This javascript code will be applied to the station public pages and login page.</translate>
                                </template>
                                <template #default="props">
                                    <codemirror-textarea :id="props.id" mode="javascript"
                                                         v-model="props.field.$model"></codemirror-textarea>
                                </template>
                            </b-wrapped-form-group>

                            <b-wrapped-form-group class="col-md-12" id="edit_form_internal_custom_css"
                                                  :field="$v.form.internal_custom_css">
                                <template #label>
                                    <translate
                                        key="lang_edit_form_internal_custom_css">Custom CSS for Internal Pages</translate>
                                </template>
                                <template #description>
                                    <translate key="lang_edit_form_internal_custom_css_desc">This CSS will be applied to the main management pages, like this one.</translate>
                                </template>
                                <template #default="props">
                                    <codemirror-textarea :id="props.id" mode="css"
                                                         v-model="props.field.$model"></codemirror-textarea>
                                </template>
                            </b-wrapped-form-group>
                        </b-row>

                        <b-button size="lg" type="submit" variant="primary">
                            <translate key="lang_btn_save_changes">Save Changes</translate>
                        </b-button>
                    </b-form-group>
                </div>
            </b-overlay>
        </section>
    </form>
</template>

<script>
import {validationMixin} from "vuelidate";
import CodemirrorTextarea from "~/components/Common/CodemirrorTextarea";
import BWrappedFormGroup from "~/components/Form/BWrappedFormGroup";

export default {
    name: 'BrandingForm',
    props: {
        apiUrl: String
    },
    components: {
        BWrappedFormGroup,
        CodemirrorTextarea,
    },
    mixins: [
        validationMixin
    ],
    data() {
        return {
            loading: true,
            error: null,
            form: {},
        };
    },
    computed: {
        publicThemeOptions() {
            return [
                {
                    text: this.$gettext('Prefer System Default'),
                    value: 'browser',
                },
                {
                    text: this.$gettext('Light'),
                    value: 'light',
                },
                {
                    text: this.$gettext('Dark'),
                    value: 'dark',
                }
            ];
        },
    },
    validations: {
        form: {
            'public_theme': {},
            'hide_album_art': {},
            'homepage_redirect_url': {},
            'default_album_art_url': {},
            'hide_product_name': {},
            'public_custom_css': {},
            'public_custom_js': {},
            'internal_custom_css': {}
        }
    },
    mounted() {
        this.relist();
    },
    methods: {
        relist() {
            this.$v.form.$reset();
            this.loading = true;

            this.axios.get(this.apiUrl).then((resp) => {
                this.populateForm(resp.data);
                this.loading = false;
            }).catch((error) => {
                this.$handleAxiosError(error);
                this.close();
            });
        },
        populateForm(data) {
            this.form = {
                'public_theme': data.public_theme,
                'hide_album_art': data.hide_album_art,
                'homepage_redirect_url': data.homepage_redirect_url,
                'default_album_art_url': data.default_album_art_url,
                'hide_product_name': data.hide_product_name,
                'public_custom_css': data.public_custom_css,
                'public_custom_js': data.public_custom_js,
                'internal_custom_css': data.internal_custom_css
            }
        },
        submit() {
            this.$v.form.$touch();
            if (this.$v.form.$anyError) {
                return;
            }

            this.axios({
                method: 'PUT',
                url: this.apiUrl,
                data: this.form
            }).then((resp) => {
                this.$notifySuccess(this.$gettext('Changes saved.'));
                this.relist();
            }).catch((error) => {
                this.error = this.$handleAxiosError(error);
            });

        }
    }
}
</script>

