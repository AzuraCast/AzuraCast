<template>
    <o-tab-item
        :label="$gettext('Basic Information')"
        active
    >
        <div class="row g-3">
            <form-group-field
                id="form_edit_title"
                class="col-md-6"
                :field="form.title"
                :label="$gettext('Episode')"
            />

            <form-group-field
                id="form_edit_link"
                class="col-md-6"
                :field="form.link"
                :label="$gettext('Website')"
                :description="$gettext('Typically a website with content about the episode.')"
            />

            <form-group-field
                id="form_edit_description"
                class="col-md-12"
                :field="form.description"
                input-type="textarea"
                :label="$gettext('Description')"
                :description="$gettext('The description of the episode. The typical maximum amount of text allowed for this is 4000 characters.')"
            />

            <form-group-field
                id="form_edit_publish_date"
                class="col-md-6"
                input-type="date"
                :field="form.publish_date"
                :label="$gettext('Publish Date')"
                :description="$gettext('The date when the episode should be published.')"
            />

            <form-group-field
                id="form_edit_publish_time"
                class="col-md-6"
                input-type="time"
                :field="form.publish_time"
                :label="$gettext('Publish Time')"
                :description="$gettext('The time when the episode should be published (according to the stations timezone).')"
            />

            <form-group-checkbox
                id="form_edit_explicit"
                class="col-md-12"
                :field="form.explicit"
                :label="$gettext('Contains explicit content')"
                :description="$gettext('Indicates the presence of explicit content (explicit language or adult content). Apple Podcasts displays an Explicit parental advisory graphic for your episode if turned on. Episodes containing explicit material aren\'t available in some Apple Podcasts territories.')"
            />
        </div>
    </o-tab-item>
</template>

<script setup>
import FormGroupField from "~/components/Form/FormGroupField";
import FormGroupCheckbox from "~/components/Form/FormGroupCheckbox";

const props = defineProps({
    form: {
        type: Object,
        required: true
    },
    locale: {
        type: String,
        required: true
    }
});
</script>
