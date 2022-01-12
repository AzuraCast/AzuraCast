<template>
    <b-tab :title="langTitle" active>
        <b-form-group>
            <b-form-row>

                <b-wrapped-form-group class="col-md-6" id="form_edit_title" :field="form.title">
                    <template #label="{lang}">
                        <translate :key="lang">Podcast Title</translate>
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group class="col-md-6" id="form_edit_link" :field="form.link">
                    <template #label="{lang}">
                        <translate :key="lang">Website</translate>
                    </template>
                    <template #description="{lang}">
                        <translate :key="lang">Typically the home page of a podcast.</translate>
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group class="col-md-12" id="form_edit_description" :field="form.description"
                                      input-type="textarea">
                    <template #label="{lang}">
                        <translate :key="lang">Description</translate>
                    </template>
                    <template #description="{lang}">
                        <translate :key="lang">The description of your podcast. The typical maximum amount of text allowed for this is 4000 characters.</translate>
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group class="col-md-12" id="form_edit_language" :field="form.language">
                    <template #label="{lang}">
                        <translate :key="lang">Language</translate>
                    </template>
                    <template #description="{lang}">
                        <translate :key="lang">The language spoken on the podcast.</translate>
                    </template>
                    <template #default="props">
                        <b-form-select :id="props.id" v-model="props.field.$model" :options="languageOptions"
                                       :state="props.state"></b-form-select>
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group class="col-md-6" id="form_edit_author" :field="form.author">
                    <template #label="{lang}">
                        <translate :key="lang">Author</translate>
                    </template>
                    <template #description="{lang}">
                        <translate :key="lang">The contact person of the podcast. May be required in order to list the podcast on services like Apple Podcasts, Spotify, Google Podcasts, etc.</translate>
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group class="col-md-6" id="form_edit_email" :field="form.email" input-type="email">
                    <template #label="{lang}">
                        <translate :key="lang">E-Mail</translate>
                    </template>
                    <template #description="{lang}">
                        <translate :key="lang">The email of the podcast contact. May be required in order to list the podcast on services like Apple Podcasts, Spotify, Google Podcasts, etc.</translate>
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group class="col-md-12" id="form_edit_categories" :field="form.categories">
                    <template #label="{lang}">
                        <translate :key="lang">Categories</translate>
                    </template>
                    <template #description="{lang}">
                        <translate :key="lang">Select the category/categories that best reflects the content of your podcast.</translate>
                    </template>
                    <template #default="props">
                        <b-form-select :id="props.id" v-model="props.field.$model" :options="categoriesOptions"
                                       :state="props.state" multiple></b-form-select>
                    </template>
                </b-wrapped-form-group>

            </b-form-row>
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
