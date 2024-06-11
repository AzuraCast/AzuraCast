<template>
    <div class="row g-3">
        <form-group-field
            id="edit_form_name"
            class="col-md-12"
            :field="form.name"
            :label="$gettext('New Station Name')"
        />

        <form-group-field
            id="edit_form_description"
            class="col-md-12"
            :field="form.description"
            input-type="textarea"
            :label="$gettext('New Station Description')"
        />

        <form-group-multi-check
            id="edit_form_clone"
            class="col-md-12"
            :field="form.clone"
            :options="cloneOptions"
            stacked
            :label="$gettext('Copy to New Station')"
        />
    </div>
</template>

<script setup lang="ts">
import FormGroupField from "~/components/Form/FormGroupField.vue";
import {computed} from "vue";
import {useTranslate} from "~/vendor/gettext";
import FormGroupMultiCheck from "~/components/Form/FormGroupMultiCheck.vue";

const props = defineProps({
    form: {
        type: Object,
        required: true
    }
});

const {$gettext} = useTranslate();

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
