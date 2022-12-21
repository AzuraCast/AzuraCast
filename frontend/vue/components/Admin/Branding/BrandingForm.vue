<template>
    <form class="form vue-form" @submit.prevent="submit">
        <section class="card mb-3" role="region">
            <div class="card-header bg-primary-dark">
                <h2 class="card-title">
                    {{ $gettext('Branding Settings') }}
                </h2>
            </div>

            <b-alert variant="danger" :show="error != null">{{ error }}</b-alert>

            <b-overlay variant="card" :show="loading">
                <div class="card-body">
                    <b-form-group>
                        <div class="form-row">
                            <b-wrapped-form-group class="col-md-6" id="edit_form_public_theme"
                                                  :field="v$.form.public_theme">
                                <template #label>
                                    {{ $gettext('Base Theme for Public Pages') }}
                                </template>
                                <template #description>
                                    {{
                                        $gettext('Select a theme to use as a base for station public pages and the login page.')
                                    }}
                                </template>
                                <template #default="props">
                                    <b-form-radio-group stacked :id="props.id" :options="publicThemeOptions"
                                                        v-model="props.field.$model">
                                    </b-form-radio-group>
                                </template>
                            </b-wrapped-form-group>

                            <b-col md="6">
                                <b-wrapped-form-checkbox class="mb-2" id="form_edit_hide_album_art"
                                                         :field="v$.form.hide_album_art">
                                    <template #label>
                                        {{ $gettext('Hide Album Art on Public Pages') }}
                                    </template>
                                    <template #description>
                                        {{
                                            $gettext('If selected, album art will not display on public-facing radio pages.')
                                        }}
                                    </template>
                                </b-wrapped-form-checkbox>

                                <b-wrapped-form-checkbox id="form_edit_hide_product_name"
                                                         :field="v$.form.hide_product_name">
                                    <template #label>
                                        {{ $gettext('Hide AzuraCast Branding on Public Pages') }}
                                    </template>
                                    <template #description>
                                        {{
                                            $gettext('If selected, this will remove the AzuraCast branding from public-facing pages.')
                                        }}
                                    </template>
                                </b-wrapped-form-checkbox>
                            </b-col>

                            <b-wrapped-form-group class="col-md-6" id="form_edit_homepage_redirect_url"
                                                  :field="v$.form.homepage_redirect_url">
                                <template #label>
                                    {{ $gettext('Homepage Redirect URL') }}
                                </template>
                                <template #description>
                                    {{
                                        $gettext('If a visitor is not signed in and visits the AzuraCast homepage, you can automatically redirect them to the URL specified here. Leave blank to redirect them to the login screen by default.')
                                    }}
                                </template>
                            </b-wrapped-form-group>

                            <b-wrapped-form-group class="col-md-6" id="form_edit_default_album_art_url"
                                                  :field="v$.form.default_album_art_url">
                                <template #label>
                                    {{ $gettext('Default Album Art URL') }}
                                </template>
                                <template #description>
                                    {{
                                        $gettext('If a song has no album art, this URL will be listed instead. Leave blank to use the standard placeholder art.')
                                    }}
                                </template>
                            </b-wrapped-form-group>

                            <b-wrapped-form-group class="col-md-12" id="edit_form_public_custom_css"
                                                  :field="v$.form.public_custom_css">
                                <template #label>
                                    {{ $gettext('Custom CSS for Public Pages') }}
                                </template>
                                <template #description>
                                    {{
                                        $gettext('This CSS will be applied to the station public pages and login page.')
                                    }}
                                </template>
                                <template #default="props">
                                    <codemirror-textarea :id="props.id" mode="css"
                                                         v-model="props.field.$model"></codemirror-textarea>
                                </template>
                            </b-wrapped-form-group>

                            <b-wrapped-form-group class="col-md-12" id="edit_form_public_custom_js"
                                                  :field="v$.form.public_custom_js">
                                <template #label>
                                    {{ $gettext('Custom JS for Public Pages') }}
                                </template>
                                <template #description>
                                    {{
                                        $gettext('This javascript code will be applied to the station public pages and login page.')
                                    }}
                                </template>
                                <template #default="props">
                                    <codemirror-textarea :id="props.id" mode="javascript"
                                                         v-model="props.field.$model"></codemirror-textarea>
                                </template>
                            </b-wrapped-form-group>

                            <b-wrapped-form-group class="col-md-12" id="edit_form_internal_custom_css"
                                                  :field="v$.form.internal_custom_css">
                                <template #label>
                                    {{ $gettext('Custom CSS for Internal Pages') }}
                                </template>
                                <template #description>
                                    {{
                                        $gettext('This CSS will be applied to the main management pages, like this one.')
                                    }}
                                </template>
                                <template #default="props">
                                    <codemirror-textarea :id="props.id" mode="css"
                                                         v-model="props.field.$model"></codemirror-textarea>
                                </template>
                            </b-wrapped-form-group>
                        </div>

                        <b-button size="lg" type="submit" class="mt-3" variant="primary">
                            {{ $gettext('Save Changes') }}
                        </b-button>
                    </b-form-group>
                </div>
            </b-overlay>
        </section>
    </form>
</template>

<script>
import useVuelidate from "@vuelidate/core";
import CodemirrorTextarea from "~/components/Common/CodemirrorTextarea";
import BWrappedFormGroup from "~/components/Form/BWrappedFormGroup";
import BWrappedFormCheckbox from "~/components/Form/BWrappedFormCheckbox";

export default {
    name: 'BrandingForm',
    props: {
        apiUrl: String
    },
    components: {
        BWrappedFormCheckbox,
        BWrappedFormGroup,
        CodemirrorTextarea,
    },
    setup() {
        return {v$: useVuelidate()}
    },
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
            this.v$.$reset();
            this.loading = true;

            this.axios.get(this.apiUrl).then((resp) => {
                this.populateForm(resp.data);
                this.loading = false;
            }).catch(() => {
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
            this.v$.$touch();
            if (this.v$.$errors.length > 0) {
                return;
            }

            this.$wrapWithLoading(
                this.axios({
                    method: 'PUT',
                    url: this.apiUrl,
                    data: this.form
                })
            ).then(() => {
                this.$notifySuccess(this.$gettext('Changes saved.'));
                this.relist();
            });

        }
    }
}
</script>

