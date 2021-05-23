<template>
    <b-tab :title="langTitle" active>
        <b-form-group>
            <b-row>
                <b-form-group class="col-md-6" label-for="form_edit_title">
                    <template #label>
                        <translate key="lang_form_edit_title">Episode</translate>
                    </template>
                    <b-form-input id="form_edit_title" type="text" v-model="form.title.$model"
                                  :state="form.title.$dirty ? !form.title.$error : null"></b-form-input>
                    <b-form-invalid-feedback>
                        <translate key="lang_error_required">This field is required.</translate>
                    </b-form-invalid-feedback>
                </b-form-group>

                <b-form-group class="col-md-6" label-for="form_edit_link">
                    <template #label>
                        <translate key="lang_form_edit_link">Website</translate>
                    </template>
                    <template #description>
                        <translate key="lang_form_edit_link_desc">Typically a website with content about the episode.</translate>
                    </template>
                    <b-form-input id="form_edit_link" type="text" v-model="form.link.$model"
                                  :state="form.link.$dirty ? !form.link.$error : null"></b-form-input>
                </b-form-group>

                <b-form-group class="col-md-12" label-for="form_edit_description">
                    <template #label>
                        <translate key="lang_form_edit_description">Description</translate>
                    </template>
                    <template #description>
                        <translate key="lang_form_edit_description_desc">The description of the episode. The typical maximum amount of text allowed for this is 4000 characters.</translate>
                    </template>
                    <b-form-textarea id="form_edit_description" v-model="form.description.$model"
                                     :state="form.description.$dirty ? !form.description.$error : null"></b-form-textarea>
                </b-form-group>

                <b-form-group class="col-md-6" label-for="form_edit_publish_date">
                    <template #label>
                        <translate key="lang_form_edit_publish_date">Publish Date</translate>
                    </template>
                    <template #description>
                        <translate key="lang_form_edit_publish_date_desc">The date when the episode should be published.</translate>
                    </template>
                    <b-form-datepicker id="form_edit_publish_date" v-model="form.publish_date.$model"
                                       :state="form.publish_date.$dirty ? !form.publish_date.$error : null" :locale="locale"></b-form-datepicker>
                </b-form-group>

                <b-form-group class="col-md-6" label-for="form_edit_publish_time">
                    <template #label>
                        <translate key="lang_form_edit_publish_time">Publish Time</translate>
                    </template>
                    <template #description>
                        <translate key="lang_form_edit_publish_time_desc">The time when the episode should be published (according to the stations timezone).</translate>
                    </template>
                    <b-form-timepicker id="form_edit_publish_time" v-model="form.publish_time.$model"
                                       :state="form.publish_time.$dirty ? !form.publish_time.$error : null" :locale="locale"></b-form-timepicker>
                </b-form-group>

                <b-form-group class="col-md-12" label-for="form_edit_explicit">
                    <template #description>
                        <translate key="lang_form_edit_explicit_desc">Indicates the presence of explicit content (explicit language or adult content). Apple Podcasts displays an Explicit parental advisory graphic for your episode if turned on. Episodes containing explicit material aren’t available in some Apple Podcasts territories.</translate>
                    </template>
                    <b-form-checkbox id="form_edit_explicit" v-model="form.explicit.$model">
                        <translate key="lang_form_edit_explicit">Contains explicit content</translate>
                    </b-form-checkbox>
                </b-form-group>
            </b-row>
        </b-form-group>
    </b-tab>
</template>

<script>
export default {
    name: 'EpisodeFormBasicInfo',
    props: {
        form: Object,
        locale: String
    },
    computed: {
        langTitle () {
            return this.$gettext('Basic Information');
        }
    }
};
</script>
