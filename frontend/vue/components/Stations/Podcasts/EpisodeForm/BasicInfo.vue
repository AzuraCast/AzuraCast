<template>
    <b-tab :title="langTitle" active>
        <b-form-group>
            <b-form-row>

                <b-wrapped-form-group class="col-md-6" id="form_edit_title" :field="form.title">
                    <template #label="{lang}">
                        {{ $gettext('Episode') }}
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group class="col-md-6" id="form_edit_link" :field="form.link">
                    <template #label="{lang}">
                        {{ $gettext('Website') }}
                    </template>
                    <template #description="{lang}">
                        {{ $gettext('Typically a website with content about the episode.') }}
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group class="col-md-12" id="form_edit_description" :field="form.description"
                                      input-type="textarea">
                    <template #label="{lang}">
                        {{ $gettext('Description') }}
                    </template>
                    <template #description="{lang}">
                        {{ $gettext('The description of the episode. The typical maximum amount of text allowed for this is 4000 characters.') }}
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group class="col-md-6" id="form_edit_publish_date" :field="form.publish_date">
                    <template #label="{lang}">
                        {{ $gettext('Publish Date') }}
                    </template>
                    <template #description="{lang}">
                        {{ $gettext('The date when the episode should be published.') }}
                    </template>
                    <template #default="props">
                        <b-form-datepicker :id="props.id" v-model="props.field.$model" :state="props.state"
                                           :locale="locale"></b-form-datepicker>
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group class="col-md-6" id="form_edit_publish_time" :field="form.publish_time">
                    <template #label="{lang}">
                        {{ $gettext('Publish Time') }}
                    </template>
                    <template #description="{lang}">
                        {{ $gettext('The time when the episode should be published (according to the stations timezone).') }}
                    </template>
                    <template #default="props">
                        <b-form-timepicker :id="props.id" v-model="props.field.$model"
                                           :state="props.state" :locale="locale"></b-form-timepicker>
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-checkbox class="col-md-12" id="form_edit_explicit" :field="form.explicit">
                    <template #label="{lang}">
                        {{ $gettext('Contains explicit content') }}
                    </template>
                    <template #description="{lang}">
                        {{ $gettext('Indicates the presence of explicit content (explicit language or adult content). Apple Podcasts displays an Explicit parental advisory graphic for your episode if turned on. Episodes containing explicit material aren’t available in some Apple Podcasts territories.') }}
                    </template>
                </b-wrapped-form-checkbox>
            </b-form-row>
        </b-form-group>
    </b-tab>
</template>

<script>
import BWrappedFormGroup from "~/components/Form/BWrappedFormGroup";
import BWrappedFormCheckbox from "~/components/Form/BWrappedFormCheckbox";

export default {
    name: 'EpisodeFormBasicInfo',
    components: {BWrappedFormCheckbox, BWrappedFormGroup},
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
