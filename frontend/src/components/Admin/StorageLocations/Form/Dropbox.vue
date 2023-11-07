<template>
    <tab
        :label="$gettext('Remote: Dropbox')"
        :item-header-class="tabClass"
    >
        <div class="row g-3">
            <div class="col-md-12">
                <h3>{{ $gettext('Dropbox Setup Instructions') }}</h3>

                <ul>
                    <li>
                        {{ $gettext('Visit the Dropbox App Console:') }}<br>
                        <a
                            href="https://www.dropbox.com/developers/apps"
                            target="_blank"
                        >
                            {{ $gettext('Dropbox App Console') }}
                        </a>
                    </li>
                    <li>
                        {{
                            $gettext('Create a new application. Choose "Scoped Access", select your preferred level of access, then name your app. Do not name it "AzuraCast", but rather use a name specific to your installation.')
                        }}
                    </li>
                    <li>
                        {{ $gettext('Enter your app secret and app key below.') }}
                    </li>
                </ul>
            </div>

            <form-group-field
                id="form_edit_dropboxAppKey"
                class="col-md-6"
                :field="v$.dropboxAppKey"
                :label="$gettext('App Key')"
            />

            <form-group-field
                id="form_edit_dropboxAppSecret"
                class="col-md-6"
                :field="v$.dropboxAppSecret"
                :label="$gettext('App Secret')"
            />

            <div class="col-md-12">
                <ul>
                    <li>
                        {{ $gettext('Visit the link below to sign in and generate an access code:') }}<br>
                        <a
                            :href="authUrl"
                            target="_blank"
                        >
                            {{ $gettext('Generate Access Code') }}
                        </a>
                    </li>
                    <li>
                        {{ $gettext('Enter the access code you receive below.') }}
                    </li>
                </ul>
            </div>

            <form-group-field
                id="form_edit_dropboxAuthToken"
                class="col-md-12"
                :field="v$.dropboxAuthToken"
                :label="$gettext('Access Code')"
            />
        </div>
    </tab>
</template>

<script setup lang="ts">
import FormGroupField from "~/components/Form/FormGroupField.vue";
import {computed} from "vue";
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
        dropboxAppKey: {},
        dropboxAppSecret: {},
        dropboxAuthToken: {required},
    },
    form
);

const baseAuthUrl = 'https://www.dropbox.com/oauth2/authorize';

const authUrl = computed(() => {
    const params = new URLSearchParams();
    params.append('client_id', form.value?.dropboxAppKey);
    params.append('response_type', 'code');
    params.append('token_access_type', 'offline');

    return baseAuthUrl + '?' + params.toString();
});
</script>
