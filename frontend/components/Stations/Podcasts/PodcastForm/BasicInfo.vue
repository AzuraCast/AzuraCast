<template>
    <tab
        :label="$gettext('Basic Information')"
        :item-header-class="tabClass"
    >
        <div class="row g-3">
            <form-group-field
                id="form_edit_title"
                class="col-md-6"
                :field="v$.title"
                :label="$gettext('Podcast Title')"
            />

            <form-group-field
                id="form_edit_link"
                class="col-md-6"
                :field="v$.link"
                :label="$gettext('Website')"
                :description="$gettext('Typically the home page of a podcast.')"
            />

            <form-group-field
                id="form_edit_description"
                class="col-md-12"
                :field="v$.description"
                input-type="textarea"
                :label="$gettext('Description')"
                :description="$gettext('The description of your podcast. The typical maximum amount of text allowed for this is 4000 characters.')"
            />

            <form-group-select
                id="form_edit_language"
                class="col-md-12"
                :field="v$.language"
                :options="languageOptions"
                :label="$gettext('Language')"
                :description="$gettext('The language spoken on the podcast.')"
            />

            <form-group-field
                id="form_edit_author"
                class="col-md-6"
                :field="v$.author"
                :label="$gettext('Author')"
                :description="$gettext('The contact person of the podcast. May be required in order to list the podcast on services like Apple Podcasts, Spotify, Google Podcasts, etc.')"
            />

            <form-group-field
                id="form_edit_email"
                class="col-md-6"
                :field="v$.email"
                input-type="email"
                :label="$gettext('E-Mail')"
                :description="$gettext('The email of the podcast contact. May be required in order to list the podcast on services like Apple Podcasts, Spotify, Google Podcasts, etc.')"
            />

            <form-group-select
                id="form_edit_categories"
                class="col-md-12"
                :field="v$.categories"
                :options="categoriesOptions"
                multiple
                :label="$gettext('Categories')"
                :description="$gettext('Select the category/categories that best reflects the content of your podcast.')"
            />

            <form-group-checkbox
                id="edit_form_is_enabled"
                class="col-md-12"
                :field="v$.is_enabled"
                :label="$gettext('Enable on Public Pages')"
                :description="$gettext('If disabled, the station will not be visible on public-facing pages or APIs.')"
            />
        </div>
    </tab>
</template>

<script setup lang="ts">
import FormGroupField from "~/components/Form/FormGroupField.vue";
import FormGroupSelect from "~/components/Form/FormGroupSelect.vue";
import {FormTabEmits, FormTabProps, useVuelidateOnFormTab} from "~/functions/useVuelidateOnFormTab";
import {required} from "@vuelidate/validators";
import Tab from "~/components/Common/Tab.vue";
import FormGroupCheckbox from "~/components/Form/FormGroupCheckbox.vue";
import {NestedFormOptionInput} from "~/functions/objectToFormOptions.ts";

interface PodcastBasicInfoFormProps extends FormTabProps {
    languageOptions: NestedFormOptionInput,
    categoriesOptions: NestedFormOptionInput,
}

const props = defineProps<PodcastBasicInfoFormProps>();
const emit = defineEmits<FormTabEmits>();

const {v$, tabClass} = useVuelidateOnFormTab(
    props,
    emit,
    {
        title: {required},
        link: {},
        description: {required},
        language: {required},
        author: {},
        email: {},
        categories: {required},
        is_enabled: {},
    },
    {
        title: '',
        link: '',
        description: '',
        language: 'en',
        author: '',
        email: '',
        categories: [],
        is_enabled: true
    }
);
</script>
