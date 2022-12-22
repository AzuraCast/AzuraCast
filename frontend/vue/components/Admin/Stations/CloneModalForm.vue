<template>
    <div class="form-row">
        <b-wrapped-form-group class="col-md-12" id="edit_form_name" :field="form.name">
            <template #label>
                {{ $gettext('New Station Name') }}
            </template>
        </b-wrapped-form-group>

        <b-wrapped-form-group class="col-md-12" id="edit_form_description" :field="form.description"
                              input-type="textarea">
            <template #label>
                {{ $gettext('New Station Description') }}
            </template>
        </b-wrapped-form-group>

        <b-wrapped-form-group class="col-md-12" id="edit_form_clone" :field="form.clone">
            <template #label>
                {{ $gettext('Copy to New Station') }}
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
    </div>
</template>

<script setup>
import BWrappedFormGroup from "~/components/Form/BWrappedFormGroup";
import {computed} from "vue";
import gettext from "~/vendor/gettext";

const props = defineProps({
    form: Object
});

const {$gettext} = gettext;

const cloneOptions = computed(() => {
    return [
        {
            text: $gettext('Share Media Storage Location'),
            value: 'media_storage'
        },
        {
            text: $gettext('Share Recordings Storage Location'),
            value: 'recordings_storage'
        },
        {
            text: $gettext('Share Podcasts Storage Location'),
            value: 'podcasts_storage'
        },
        {
            text: $gettext('Playlists'),
            value: 'playlists',
        },
        {
            text: $gettext('Mount Points'),
            value: 'mounts'
        },
        {
            text: $gettext('Remote Relays'),
            value: 'remotes'
        },
        {
            text: $gettext('Streamers/DJs'),
            value: 'streamers'
        },
        {
            text: $gettext('User Permissions'),
            value: 'permissions'
        },
        {
            text: $gettext('Web Hooks'),
            value: 'webhooks'
        }
    ];
});
</script>
