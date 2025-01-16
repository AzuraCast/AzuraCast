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
                :label="$gettext('Episode')"
            />

            <form-group-field
                id="form_edit_link"
                class="col-md-6"
                :field="v$.link"
                :label="$gettext('Website')"
                :description="$gettext('Typically a website with content about the episode.')"
            />

            <form-group-field
                id="form_edit_description"
                class="col-md-12"
                :field="v$.description"
                input-type="textarea"
                :label="$gettext('Description')"
                :description="$gettext('The description of the episode. The typical maximum amount of text allowed for this is 4000 characters.')"
            />

            <form-group-field
                id="form_edit_publish_at"
                class="col-md-12"
                :field="v$.publish_at"
                :label="$gettext('Publish At')"
                :description="$gettext('The date and time when the episode should be published.')"
            >
                <template #default="{id, model, fieldClass}">
                    <publish-at-fields
                        :id="id"
                        v-model="model.$model"
                        :class="fieldClass"
                    />
                </template>
            </form-group-field>

            <form-group-checkbox
                id="form_edit_explicit"
                class="col-md-12"
                :field="v$.explicit"
                :label="$gettext('Contains explicit content')"
                :description="$gettext('Indicates the presence of explicit content (explicit language or adult content). Apple Podcasts displays an Explicit parental advisory graphic for your episode if turned on. Episodes containing explicit material aren\'t available in some Apple Podcasts territories.')"
            />

            <form-group-field
                id="form_edit_season_number"
                class="col-md-6"
                :field="v$.season_number"
                input-type="number"
                :input-attrs="{ step: '1' }"
                :label="$gettext('Season Number')"
                :description="$gettext('Optionally list this episode as part of a season in some podcast aggregators.')"
                clearable
            />

            <form-group-field
                id="form_edit_episode_number"
                class="col-md-6"
                :field="v$.episode_number"
                input-type="number"
                :input-attrs="{ step: '1' }"
                :label="$gettext('Episode Number')"
                :description="$gettext('Optionally set a specific episode number in some podcast aggregators.')"
                clearable
            />
        </div>
    </tab>
</template>

<script setup lang="ts">
import FormGroupField from "~/components/Form/FormGroupField.vue";
import FormGroupCheckbox from "~/components/Form/FormGroupCheckbox.vue";
import {FormTabEmits, FormTabProps, useVuelidateOnFormTab} from "~/functions/useVuelidateOnFormTab";
import {required} from "@vuelidate/validators";
import Tab from "~/components/Common/Tab.vue";
import PublishAtFields from "~/components/Stations/Podcasts/Common/PublishAtFields.vue";

const props = defineProps<FormTabProps>();
const emit = defineEmits<FormTabEmits>();

const {v$, tabClass} = useVuelidateOnFormTab(
    props,
    emit,
    {
        title: {required},
        link: {},
        description: {required},
        publish_at: {},
        explicit: {},
        season_number: {},
        episode_number: {}
    },
    {
        title: '',
        link: '',
        description: '',
        publish_at: null,
        explicit: false,
        season_number: null,
        episode_number: null
    }
);
</script>
