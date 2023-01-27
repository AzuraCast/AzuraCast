<template>
    <b-tab
        :title="$gettext('Basic Info')"
        active
    >
        <b-form-group>
            <div class="form-row">
                <b-wrapped-form-group
                    id="edit_form_type"
                    class="col-md-12"
                    :field="form.type"
                >
                    <template #label>
                        {{ $gettext('Remote Station Type') }}
                    </template>
                    <template #default="slotProps">
                        <b-form-radio-group
                            :id="slotProps.id"
                            v-model="slotProps.field.$model"
                            stacked
                            :state="slotProps.state"
                            :options="typeOptions"
                        />
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group
                    id="edit_form_display_name"
                    class="col-md-6"
                    :field="form.display_name"
                >
                    <template #label>
                        {{ $gettext('Display Name') }}
                    </template>
                    <template #description>
                        {{
                            $gettext('The display name assigned to this relay when viewing it on administrative or public pages. Leave blank to automatically generate one.')
                        }}
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group
                    id="edit_form_url"
                    class="col-md-6"
                    :field="form.url"
                >
                    <template #label>
                        {{ $gettext('Remote Station Listening URL') }}
                    </template>
                    <template #description>
                        {{
                            $gettext('Example: if the remote radio URL is http://station.example.com:8000/radio.mp3, enter "http://station.example.com:8000".')
                        }}
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group
                    id="edit_form_mount"
                    class="col-md-6"
                    :field="form.mount"
                >
                    <template #label>
                        {{ $gettext('Remote Station Listening Mountpoint/SID') }}
                    </template>
                    <template #description>
                        {{
                            $gettext('Specify a mountpoint (i.e. "/radio.mp3") or a Shoutcast SID (i.e. "2") to specify a specific stream to use for statistics or broadcasting.')
                        }}
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group
                    id="edit_form_admin_password"
                    class="col-md-6"
                    :field="form.admin_password"
                >
                    <template #label>
                        {{ $gettext('Remote Station Administrator Password') }}
                    </template>
                    <template #description>
                        {{
                            $gettext('To retrieve detailed unique listeners and client details, an administrator password is often required.')
                        }}
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-checkbox
                    id="edit_form_is_visible_on_public_pages"
                    class="col-md-6"
                    :field="form.is_visible_on_public_pages"
                >
                    <template #label>
                        {{ $gettext('Show on Public Pages') }}
                    </template>
                    <template #description>
                        {{
                            $gettext('Enable to allow listeners to select this relay on this station\'s public pages.')
                        }}
                    </template>
                </b-wrapped-form-checkbox>
            </div>
        </b-form-group>
    </b-tab>
</template>

<script setup>
import {REMOTE_ICECAST, REMOTE_SHOUTCAST1, REMOTE_SHOUTCAST2} from '~/components/Entity/RadioAdapters';
import BWrappedFormGroup from "~/components/Form/BWrappedFormGroup";
import BWrappedFormCheckbox from "~/components/Form/BWrappedFormCheckbox";

const props = defineProps({
    form: {
        type: Object,
        required: true
    }
});

const typeOptions = [
    {
        value: REMOTE_ICECAST,
        text: 'Icecast v2.4+',
    },
    {
        value: REMOTE_SHOUTCAST1,
        text: 'Shoutcast v1',
    },
    {
        value: REMOTE_SHOUTCAST2,
        text: 'Shoutcast v2',
    }
];
</script>
