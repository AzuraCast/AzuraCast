<template>
    <tab
        :label="$gettext('Basic Info')"
        :item-header-class="tabClass"
    >
        <div class="row g-3">
            <form-group-multi-check
                id="edit_form_type"
                class="col-md-12"
                :field="v$.type"
                :options="typeOptions"
                stacked
                radio
                :label="$gettext('Remote Station Type')"
            />

            <form-group-field
                id="edit_form_display_name"
                class="col-md-6"
                :field="v$.display_name"
                :label="$gettext('Display Name')"
                :description="$gettext('The display name assigned to this relay when viewing it on administrative or public pages. Leave blank to automatically generate one.')"
            />

            <form-group-field
                id="edit_form_url"
                class="col-md-6"
                :field="v$.url"
                :label="$gettext('Remote Station Listening URL')"
            >
                <template #description>
                    {{
                        $gettext('Example: if the remote radio URL is http://station.example.com:8000/radio.mp3, enter "http://station.example.com:8000".')
                    }}
                </template>
            </form-group-field>

            <form-group-field
                id="edit_form_mount"
                class="col-md-6"
                :field="v$.mount"
                :label="$gettext('Remote Station Listening Mountpoint/SID')"
            >
                <template #description>
                    {{
                        $gettext('Specify a mountpoint (i.e. "/radio.mp3") or a Shoutcast SID (i.e. "2") to specify a specific stream to use for statistics or broadcasting.')
                    }}
                </template>
            </form-group-field>

            <form-group-field
                id="edit_form_admin_password"
                class="col-md-6"
                :field="v$.admin_password"
                :label="$gettext('Remote Station Administrator Password')"
                :description="$gettext('To retrieve detailed unique listeners and client details, an administrator password is often required.')"
            />

            <form-group-checkbox
                id="edit_form_is_visible_on_public_pages"
                class="col-md-6"
                :field="v$.is_visible_on_public_pages"
                :label="$gettext('Show on Public Pages')"
                :description="$gettext('Enable to allow listeners to select this relay on this station\'s public pages.')"
            />
        </div>
    </tab>
</template>

<script setup lang="ts">
import {RemoteAdapter} from '~/components/Entity/RadioAdapters';
import FormGroupField from "~/components/Form/FormGroupField.vue";
import FormGroupCheckbox from "~/components/Form/FormGroupCheckbox.vue";
import FormGroupMultiCheck from "~/components/Form/FormGroupMultiCheck.vue";
import {useVModel} from "@vueuse/core";
import {useVuelidateOnFormTab} from "~/functions/useVuelidateOnFormTab";
import {required} from "@vuelidate/validators";
import Tab from "~/components/Common/Tab.vue";

const props = defineProps({
    form: {
        type: Object,
        required: true
    }
});

const emit = defineEmits(['update:form']);
const form = useVModel(props, 'form', emit);

const {v$, tabClass} = useVuelidateOnFormTab(
    {
        display_name: {},
        type: {required},
        url: {required},
        mount: {},
        admin_password: {},
        is_visible_on_public_pages: {},
    },
    form,
    {
        display_name: null,
        type: RemoteAdapter.Icecast,
        custom_listen_url: null,
        url: null,
        mount: null,
        admin_password: null,
        is_visible_on_public_pages: true,
    }
);

const typeOptions = [
    {
        value: RemoteAdapter.Icecast,
        text: 'Icecast v2.4+',
    },
    {
        value: RemoteAdapter.Shoutcast1,
        text: 'Shoutcast v1',
    },
    {
        value: RemoteAdapter.Shoutcast2,
        text: 'Shoutcast v2',
    }
];
</script>
