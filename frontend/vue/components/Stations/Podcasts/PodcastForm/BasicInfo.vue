<template>
    <b-tab :title="langTitle" active>
        <b-form-group>
            <b-form-row>

                <b-wrapped-form-group class="col-md-6" id="form_edit_title" :field="form.title">
                    <template #label="{lang}">
                        {{ $gettext('Podcast Title') }}
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group class="col-md-6" id="form_edit_link" :field="form.link">
                    <template #label="{lang}">
                        {{ $gettext('Website') }}
                    </template>
                    <template #description="{lang}">
                        {{ $gettext('Typically the home page of a podcast.') }}
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group class="col-md-12" id="form_edit_description" :field="form.description"
                                      input-type="textarea">
                    <template #label="{lang}">
                        {{ $gettext('Description') }}
                    </template>
                    <template #description="{lang}">
                        {{ $gettext('The description of your podcast. The typical maximum amount of text allowed for this is 4000 characters.') }}
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group class="col-md-12" id="form_edit_language" :field="form.language">
                    <template #label="{lang}">
                        {{ $gettext('Language') }}
                    </template>
                    <template #description="{lang}">
                        {{ $gettext('The language spoken on the podcast.') }}
                    </template>
                    <template #default="props">
                        <b-form-select :id="props.id" v-model="props.field.$model" :options="languageOptions"
                                       :state="props.state"></b-form-select>
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group class="col-md-6" id="form_edit_author" :field="form.author">
                    <template #label="{lang}">
                        {{ $gettext('Author') }}
                    </template>
                    <template #description="{lang}">
                        {{ $gettext('The contact person of the podcast. May be required in order to list the podcast on services like Apple Podcasts, Spotify, Google Podcasts, etc.') }}
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group class="col-md-6" id="form_edit_email" :field="form.email" input-type="email">
                    <template #label="{lang}">
                        {{ $gettext('E-Mail') }}
                    </template>
                    <template #description="{lang}">
                        {{ $gettext('The email of the podcast contact. May be required in order to list the podcast on services like Apple Podcasts, Spotify, Google Podcasts, etc.') }}
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group class="col-md-12" id="form_edit_categories" :field="form.categories">
                    <template #label="{lang}">
                        {{ $gettext('Categories') }}
                    </template>
                    <template #description="{lang}">
                        {{ $gettext('Select the category/categories that best reflects the content of your podcast.') }}
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
