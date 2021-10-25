<template>
    <b-form-row>
        <b-wrapped-form-group class="col-md-12" id="edit_form_name" :field="form.name">
            <template #label="{lang}">
                <translate :key="lang">New Station Name</translate>
            </template>
        </b-wrapped-form-group>

        <b-wrapped-form-group class="col-md-12" id="edit_form_description" :field="form.description"
                              input-type="textarea">
            <template #label="{lang}">
                <translate :key="lang">New Station Description</translate>
            </template>
        </b-wrapped-form-group>

        <b-wrapped-form-group class="col-md-12" id="edit_form_clone" :field="form.clone">
            <template #label="{lang}">
                <translate :key="lang">Copy to New Station</translate>
            </template>
            <template #default="props">
                <b-form-checkbox-group
                    :id="props.id"
                    v-model="props.field.$model"
                    :options="cloneOptions"
                    stacked
                ></b-form-checkbox-group>
            </template>
        </b-wrapped-form-group>
    </b-form-row>
</template>

<script>
import BWrappedFormGroup from "~/components/Form/BWrappedFormGroup";

export default {
    name: 'AdminStationsCloneModalForm',
    components: {BWrappedFormGroup},
    props: {
        form: Object
    },
    computed: {
        cloneOptions() {
            return [
                {
                    text: this.$gettext('Share Media Storage Location'),
                    value: 'media_storage'
                },
                {
                    text: this.$gettext('Share Recordings Storage Location'),
                    value: 'recordings_storage'
                },
                {
                    text: this.$gettext('Share Podcasts Storage Location'),
                    value: 'podcasts_storage'
                },
                {
                    text: this.$gettext('Playlists'),
                    value: 'playlists',
                },
                {
                    text: this.$gettext('Mount Points'),
                    value: 'mounts'
                },
                {
                    text: this.$gettext('Remote Relays'),
                    value: 'remotes'
                },
                {
                    text: this.$gettext('Streamers/DJs'),
                    value: 'streamers'
                },
                {
                    text: this.$gettext('User Permissions'),
                    value: 'permissions'
                },
                {
                    text: this.$gettext('Web Hooks'),
                    value: 'webhooks'
                }
            ];
        }
    }
}
</script>
