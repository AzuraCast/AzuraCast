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
                        {{ $gettext('Episode') }}
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
                        {{ $gettext('Typically a website with content about the episode.') }}
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
                            $gettext('The description of the episode. The typical maximum amount of text allowed for this is 4000 characters.')
                        }}
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group
                    id="form_edit_publish_date"
                    class="col-md-6"
                    :field="form.publish_date"
                >
                    <template #label>
                        {{ $gettext('Publish Date') }}
                    </template>
                    <template #description>
                        {{ $gettext('The date when the episode should be published.') }}
                    </template>
                    <template #default="slotProps">
                        <b-form-datepicker
                            :id="slotProps.id"
                            v-model="slotProps.field.$model"
                            :state="slotProps.state"
                            :locale="locale"
                        />
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group
                    id="form_edit_publish_time"
                    class="col-md-6"
                    :field="form.publish_time"
                >
                    <template #label>
                        {{ $gettext('Publish Time') }}
                    </template>
                    <template #description>
                        {{
                            $gettext('The time when the episode should be published (according to the stations timezone).')
                        }}
                    </template>
                    <template #default="slotProps">
                        <b-form-timepicker
                            :id="slotProps.id"
                            v-model="slotProps.field.$model"
                            :state="slotProps.state"
                            :locale="locale"
                        />
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-checkbox
                    id="form_edit_explicit"
                    class="col-md-12"
                    :field="form.explicit"
                >
                    <template #label>
                        {{ $gettext('Contains explicit content') }}
                    </template>
                    <template #description>
                        {{
                            $gettext('Indicates the presence of explicit content (explicit language or adult content). Apple Podcasts displays an Explicit parental advisory graphic for your episode if turned on. Episodes containing explicit material aren’t available in some Apple Podcasts territories.')
                        }}
                    </template>
                </b-wrapped-form-checkbox>
            </div>
        </b-form-group>
    </o-tab-item>
</template>

<script setup>
import BWrappedFormGroup from "~/components/Form/BWrappedFormGroup";
import BWrappedFormCheckbox from "~/components/Form/BWrappedFormCheckbox";

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
