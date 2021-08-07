<template>
    <b-tab :title="langTitle" active>
        <b-form-group>
            <b-row>
                <b-form-group class="col-md-6" label-for="form_edit_title">
                    <template #label>
                        <translate key="lang_form_edit_title">Podcast Title</translate>
                    </template>
                    <b-form-input id="form_edit_title" type="text" v-model="form.title.$model"
                                  :state="form.title.$dirty ? !form.title.$error : null"></b-form-input>
                    <b-form-invalid-feedback>
                        <translate key="lang_error_required">This field is required.</translate>
                    </b-form-invalid-feedback>
                </b-form-group>

                <b-form-group class="col-md-6" label-for="form_edit_link">
                    <template #label>
                        <translate key="lang_form_edit_link">Website</translate>
                    </template>
                    <template #description>
                        <translate key="lang_form_edit_link_desc">Typically the home page of a podcast.</translate>
                    </template>
                    <b-form-input id="form_edit_link" type="text" v-model="form.link.$model"
                                  :state="form.link.$dirty ? !form.link.$error : null"></b-form-input>
                </b-form-group>

                <b-form-group class="col-md-12" label-for="form_edit_description">
                    <template #label>
                        <translate key="lang_form_edit_description">Description</translate>
                    </template>
                    <template #description>
                        <translate key="lang_form_edit_description_desc">The description of your podcast. The typical maximum amount of text allowed for this is 4000 characters.</translate>
                    </template>
                    <b-form-textarea id="form_edit_description" v-model="form.description.$model"
                                     :state="form.description.$dirty ? !form.description.$error : null"></b-form-textarea>
                </b-form-group>

                <b-form-group class="col-md-12" label-for="form_edit_language">
                    <template #label>
                        <translate key="lang_form_edit_language">Language</translate>
                    </template>
                    <template #description>
                        <translate key="lang_form_edit_language_desc">The language spoken on the podcast.</translate>
                    </template>
                    <b-form-select id="form_edit_language" v-model="form.language.$model" :options="languageOptions"
                                   :state="form.language.$dirty ? !form.language.$error : null"></b-form-select>
                    <b-form-invalid-feedback>
                        <translate key="lang_error_required">This field is required.</translate>
                    </b-form-invalid-feedback>
                </b-form-group>

                <b-form-group class="col-md-6" label-for="form_edit_author">
                    <template #label>
                        <translate key="lang_form_edit_author">Author</translate>
                    </template>
                    <template #description>
                        <translate key="lang_form_edit_author_desc">The contact person of the podcast. May be required in order to list the podcast on services like Apple Podcasts, Spotify, Google Podcasts, etc.</translate>
                    </template>
                    <b-form-input id="form_edit_author" type="text" v-model="form.author.$model"
                                  :state="form.author.$dirty ? !form.author.$error : null"></b-form-input>
                </b-form-group>

                <b-form-group class="col-md-6" label-for="form_edit_email">
                    <template #label>
                        <translate key="lang_form_edit_email">E-Mail</translate>
                    </template>
                    <template #description>
                        <translate key="lang_form_edit_email_desc">The email of the podcast contact. May be required in order to list the podcast on services like Apple Podcasts, Spotify, Google Podcasts, etc.</translate>
                    </template>
                    <b-form-input id="form_edit_email" type="email" v-model="form.email.$model"
                                  :state="form.email.$dirty ? !form.email.$error : null"></b-form-input>
                </b-form-group>

                <b-form-group class="col-md-12" label-for="form_edit_categories">
                    <template #label>
                        <translate key="lang_form_edit_categories">Categories</translate>
                    </template>
                    <template #description>
                        <translate key="lang_form_edit_categories_desc">Select the category/categories that best reflects the content of your podcast.</translate>
                    </template>
                    <b-form-select id="form_edit_categories" v-model="form.categories.$model" :options="categoriesOptions"
                                   :state="form.categories.$dirty ? !form.categories.$error : null" multiple></b-form-select>
                    <b-form-invalid-feedback>
                        <translate key="lang_error_required">This field is required.</translate>
                    </b-form-invalid-feedback>
                </b-form-group>
            </b-row>
        </b-form-group>
    </b-tab>
</template>

<script>
export default {
    name: 'PodcastFormBasicInfo',
    props: {
        form: Object,
        languageOptions: Object,
        categoriesOptions: Object
    },
    computed: {
        langTitle () {
            return this.$gettext('Basic Information');
        }
    }
};
</script>
