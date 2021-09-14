<template>
    <b-tab :title="langTitle" active>
        <b-form-group>
            <b-row>

                <b-wrapped-form-group class="col-md-6" id="form_edit_title" :field="form.title">
                    <template #label>
                        <translate key="lang_form_edit_title">Episode</translate>
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group class="col-md-6" id="form_edit_link" :field="form.link">
                    <template #label>
                        <translate key="lang_form_edit_link">Website</translate>
                    </template>
                    <template #description>
                        <translate
                            key="lang_form_edit_link_desc">Typically a website with content about the episode.</translate>
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group class="col-md-12" id="form_edit_description" :field="form.description">
                    <template #label>
                        <translate key="lang_form_edit_description">Description</translate>
                    </template>
                    <template #description>
                        <translate key="lang_form_edit_description_desc">The description of the episode. The typical maximum amount of text allowed for this is 4000 characters.</translate>
                    </template>
                    <template #default="props">
                        <b-form-textarea :id="props.id" v-model="props.field.$model"
                                         :state="props.state"></b-form-textarea>
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group class="col-md-6" id="form_edit_publish_date" :field="form.publish_date">
                    <template #label>
                        <translate key="lang_form_edit_publish_date">Publish Date</translate>
                    </template>
                    <template #description>
                        <translate
                            key="lang_form_edit_publish_date_desc">The date when the episode should be published.</translate>
                    </template>
                    <template #default="props">
                        <b-form-datepicker :id="props.id" v-model="props.field.$model" :state="props.state"
                                           :locale="locale"></b-form-datepicker>
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group class="col-md-6" id="form_edit_publish_time" :field="form.publish_time">
                    <template #label>
                        <translate key="lang_form_edit_publish_time">Publish Time</translate>
                    </template>
                    <template #description>
                        <translate key="lang_form_edit_publish_time_desc">The time when the episode should be published (according to the stations timezone).</translate>
                    </template>
                    <template #default="props">
                        <b-form-timepicker :id="props.id" v-model="props.field.$model"
                                           :state="props.state" :locale="locale"></b-form-timepicker>
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group class="col-md-12" id="form_edit_explicit" :field="form.explicit">
                    <template #description>
                        <translate key="lang_form_edit_explicit_desc">Indicates the presence of explicit content (explicit language or adult content). Apple Podcasts displays an Explicit parental advisory graphic for your episode if turned on. Episodes containing explicit material aren’t available in some Apple Podcasts territories.</translate>
                    </template>
                    <template #default="props">
                        <b-form-checkbox :id="props.id" v-model="props.field.$model">
                            <translate key="lang_form_edit_explicit">Contains explicit content</translate>
                        </b-form-checkbox>
                    </template>
                </b-wrapped-form-group>
            </b-row>
        </b-form-group>
    </b-tab>
</template>

<script>
import BWrappedFormGroup from "~/components/Form/BWrappedFormGroup";

export default {
    name: 'EpisodeFormBasicInfo',
    components: {BWrappedFormGroup},
    props: {
        form: Object,
        locale: String
    },
    computed: {
        langTitle() {
            return this.$gettext('Basic Information');
        }
    }
};
</script>
