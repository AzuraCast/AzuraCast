<template>
    <o-tab-item
        :label="$gettext('Basic Information')"
        active
    >
        <b-form-group>
            <div class="row g-3">
                <b-wrapped-form-group
                    id="form_edit_title"
                    class="col-md-6"
                    :field="form.title"
                >
                    <template #label>
                        {{ $gettext('Podcast Title') }}
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group
                    id="form_edit_link"
                    class="col-md-6"
                    :field="form.link"
                >
                    <template #label>
                        {{ $gettext('Website') }}
                    </template>
                    <template #description>
                        {{ $gettext('Typically the home page of a podcast.') }}
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group
                    id="form_edit_description"
                    class="col-md-12"
                    :field="form.description"
                    input-type="textarea"
                >
                    <template #label>
                        {{ $gettext('Description') }}
                    </template>
                    <template #description>
                        {{
                            $gettext('The description of your podcast. The typical maximum amount of text allowed for this is 4000 characters.')
                        }}
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group
                    id="form_edit_language"
                    class="col-md-12"
                    :field="form.language"
                >
                    <template #label>
                        {{ $gettext('Language') }}
                    </template>
                    <template #description>
                        {{ $gettext('The language spoken on the podcast.') }}
                    </template>
                    <template #default="slotProps">
                        <b-form-select
                            :id="slotProps.id"
                            v-model="slotProps.field.$model"
                            :options="languageOptions"
                            :state="slotProps.state"
                        />
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group
                    id="form_edit_author"
                    class="col-md-6"
                    :field="form.author"
                >
                    <template #label>
                        {{ $gettext('Author') }}
                    </template>
                    <template #description>
                        {{
                            $gettext('The contact person of the podcast. May be required in order to list the podcast on services like Apple Podcasts, Spotify, Google Podcasts, etc.')
                        }}
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group
                    id="form_edit_email"
                    class="col-md-6"
                    :field="form.email"
                    input-type="email"
                >
                    <template #label>
                        {{ $gettext('E-Mail') }}
                    </template>
                    <template #description>
                        {{
                            $gettext('The email of the podcast contact. May be required in order to list the podcast on services like Apple Podcasts, Spotify, Google Podcasts, etc.')
                        }}
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group
                    id="form_edit_categories"
                    class="col-md-12"
                    :field="form.categories"
                >
                    <template #label>
                        {{ $gettext('Categories') }}
                    </template>
                    <template #description>
                        {{ $gettext('Select the category/categories that best reflects the content of your podcast.') }}
                    </template>
                    <template #default="slotProps">
                        <b-form-select
                            :id="slotProps.id"
                            v-model="slotProps.field.$model"
                            :options="categoriesOptions"
                            :state="slotProps.state"
                            multiple
                        />
                    </template>
                </b-wrapped-form-group>
            </div>
        </b-form-group>
    </o-tab-item>
</template>

<script setup>
import BWrappedFormGroup from "~/components/Form/BWrappedFormGroup";

const props = defineProps({
    form: {
        type: Object,
        required: true
    },
    languageOptions: {
        type: Object,
        required: true
    },
    categoriesOptions: {
        type: Object,
        required: true
    }
});
</script>
