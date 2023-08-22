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
        </div>
    </tab>
</template>

<script setup>
import FormGroupField from "~/components/Form/FormGroupField";
import FormGroupSelect from "~/components/Form/FormGroupSelect.vue";
import {useVModel} from "@vueuse/core";
import {useVuelidateOnFormTab} from "~/functions/useVuelidateOnFormTab";
import {required} from "@vuelidate/validators";
import Tab from "~/components/Common/Tab.vue";

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

const emit = defineEmits(['update:form']);
const form = useVModel(props, 'form', emit);

const {v$, tabClass} = useVuelidateOnFormTab(
    {
        title: {required},
        link: {},
        description: {required},
        language: {required},
        author: {},
        email: {},
        categories: {required},
    },
    form,
    {
        title: '',
        link: '',
        description: '',
        language: 'en',
        author: '',
        email: '',
        categories: [],
    }
);
</script>
