<template>
    <b-tab :title="$gettext('Basic Information')" active>
        <b-form-group>
            <div class="form-row">

                <b-wrapped-form-group class="col-md-6" id="form_edit_title" :field="form.title">
                    <template #label>
                        {{ $gettext('Podcast Title') }}
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group class="col-md-6" id="form_edit_link" :field="form.link">
                    <template #label>
                        {{ $gettext('Website') }}
                    </template>
                    <template #description>
                        {{ $gettext('Typically the home page of a podcast.') }}
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group class="col-md-12" id="form_edit_description" :field="form.description"
                                      input-type="textarea">
                    <template #label>
                        {{ $gettext('Description') }}
                    </template>
                    <template #description>
                        {{
                            $gettext('The description of your podcast. The typical maximum amount of text allowed for this is 4000 characters.')
                        }}
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group class="col-md-12" id="form_edit_language" :field="form.language">
                    <template #label>
                        {{ $gettext('Language') }}
                    </template>
                    <template #description>
                        {{ $gettext('The language spoken on the podcast.') }}
                    </template>
                    <template #default="props">
                        <b-form-select :id="props.id" v-model="props.field.$model" :options="languageOptions"
                                       :state="props.state"></b-form-select>
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group class="col-md-6" id="form_edit_author" :field="form.author">
                    <template #label>
                        {{ $gettext('Author') }}
                    </template>
                    <template #description>
                        {{
                            $gettext('The contact person of the podcast. May be required in order to list the podcast on services like Apple Podcasts, Spotify, Google Podcasts, etc.')
                        }}
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group class="col-md-6" id="form_edit_email" :field="form.email" input-type="email">
                    <template #label>
                        {{ $gettext('E-Mail') }}
                    </template>
                    <template #description>
                        {{
                            $gettext('The email of the podcast contact. May be required in order to list the podcast on services like Apple Podcasts, Spotify, Google Podcasts, etc.')
                        }}
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group class="col-md-12" id="form_edit_categories" :field="form.categories">
                    <template #label>
                        {{ $gettext('Categories') }}
                    </template>
                    <template #description>
                        {{ $gettext('Select the category/categories that best reflects the content of your podcast.') }}
                    </template>
                    <template #default="props">
                        <b-form-select :id="props.id" v-model="props.field.$model" :options="categoriesOptions"
                                       :state="props.state" multiple></b-form-select>
                    </template>
                </b-wrapped-form-group>

            </div>
        </b-form-group>
    </b-tab>
</template>

<script setup>
import BWrappedFormGroup from "~/components/Form/BWrappedFormGroup";

const props = defineProps({
    form: Object,
    languageOptions: Object,
    categoriesOptions: Object
});
</script>
