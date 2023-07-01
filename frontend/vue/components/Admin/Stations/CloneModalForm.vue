<template>
    <div class="row g-3">
        <form-group-field
            id="edit_form_name"
            class="col-md-12"
            :field="form.name"
        >
            <template #label>
                {{ $gettext('New Station Name') }}
            </template>
        </form-group-field>

        <form-group-field
            id="edit_form_description"
            class="col-md-12"
            :field="form.description"
            input-type="textarea"
        >
            <template #label>
                {{ $gettext('New Station Description') }}
            </template>
        </form-group-field>

        <form-group-field
            id="edit_form_clone"
            class="col-md-12"
            :field="form.clone"
        >
            <template #label>
                {{ $gettext('Copy to New Station') }}
            </template>
            <template #default="slotProps">
                <b-form-checkbox-group
                    :id="slotProps.id"
                    v-model="slotProps.field.$model"
                    :options="cloneOptions"
                    stacked
                />
            </template>
        </form-group-field>
    </div>
</template>

<script setup>
import FormGroupField from "~/components/Form/FormGroupField.vue";
import {computed} from "vue";
import {useTranslate} from "~/vendor/gettext";

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
