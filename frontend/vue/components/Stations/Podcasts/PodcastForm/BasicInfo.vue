<template>
    <b-tab :title="langTitle" active>
        <b-form-group>
            <b-row>

                <b-wrapped-form-group class="col-md-6" id="form_edit_title" :field="form.title">
                    <template #label>
                        <translate key="lang_form_edit_title">Podcast Title</translate>
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group class="col-md-6" id="form_edit_link" :field="form.link">
                    <template #label>
                        <translate key="lang_form_edit_link">Website</translate>
                    </template>
                    <template #description>
                        <translate key="lang_form_edit_link_desc">Typically the home page of a podcast.</translate>
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group class="col-md-12" id="form_edit_description" :field="form.description">
                    <template #label>
                        <translate key="lang_form_edit_description">Description</translate>
                    </template>
                    <template #description>
                        <translate key="lang_form_edit_description_desc">The description of your podcast. The typical maximum amount of text allowed for this is 4000 characters.</translate>
                    </template>
                    <template #default="props">
                        <b-form-textarea :id="props.id" v-model="props.field.$model"
                                         :state="props.state"></b-form-textarea>
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group class="col-md-12" id="form_edit_language" :field="form.language">
                    <template #label>
                        <translate key="lang_form_edit_language">Language</translate>
                    </template>
                    <template #description>
                        <translate key="lang_form_edit_language_desc">The language spoken on the podcast.</translate>
                    </template>
                    <template #default="props">
                        <b-form-select :id="props.id" v-model="props.field.$model" :options="languageOptions"
                                       :state="props.state"></b-form-select>
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group class="col-md-6" id="form_edit_author" :field="form.author">
                    <template #label>
                        <translate key="lang_form_edit_author">Author</translate>
                    </template>
                    <template #description>
                        <translate key="lang_form_edit_author_desc">The contact person of the podcast. May be required in order to list the podcast on services like Apple Podcasts, Spotify, Google Podcasts, etc.</translate>
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group class="col-md-6" id="form_edit_email" :field="form.email">
                    <template #label>
                        <translate key="lang_form_edit_email">E-Mail</translate>
                    </template>
                    <template #description>
                        <translate key="lang_form_edit_email_desc">The email of the podcast contact. May be required in order to list the podcast on services like Apple Podcasts, Spotify, Google Podcasts, etc.</translate>
                    </template>
                    <template #default="props">
                        <b-form-input :id="props.id" type="email" v-model="props.field.$model"
                                      :state="props.state"></b-form-input>
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group class="col-md-12" id="form_edit_categories" :field="form.categories">
                    <template #label>
                        <translate key="lang_form_edit_categories">Categories</translate>
                    </template>
                    <template #description>
                        <translate key="lang_form_edit_categories_desc">Select the category/categories that best reflects the content of your podcast.</translate>
                    </template>
                    <template #default="props">
                        <b-form-select :id="props.id" v-model="props.field.$model" :options="categoriesOptions"
                                       :state="props.state" multiple></b-form-select>
                    </template>
                </b-wrapped-form-group>

            </b-row>
        </b-form-group>
    </b-tab>
</template>

<script>
import BWrappedFormGroup from "~/components/Form/BWrappedFormGroup";

export default {
    name: 'PodcastFormBasicInfo',
    components: {BWrappedFormGroup},
    props: {
        form: Object,
        languageOptions: Object,
        categoriesOptions: Object
    },
    computed: {
        langTitle() {
            return this.$gettext('Basic Information');
        }
    }
};
</script>
