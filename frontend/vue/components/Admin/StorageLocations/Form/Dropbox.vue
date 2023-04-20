<template>
    <section class="card mt-3">
        <div class="card-header bg-primary-dark">
            <h2 class="card-title">
                {{ $gettext('Remote: Dropbox') }}
            </h2>
        </div>
        <b-card-body>
            <b-form-group>
                <div class="form-row">
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

                    <b-wrapped-form-group
                        id="form_edit_dropboxAppKey"
                        class="col-md-6"
                        :field="form.dropboxAppKey"
                    >
                        <template #label>
                            {{ $gettext('App Key') }}
                        </template>
                    </b-wrapped-form-group>

                    <b-wrapped-form-group
                        id="form_edit_dropboxAppSecret"
                        class="col-md-6"
                        :field="form.dropboxAppSecret"
                    >
                        <template #label>
                            {{ $gettext('App Secret') }}
                        </template>
                    </b-wrapped-form-group>

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

                    <b-wrapped-form-group
                        id="form_edit_dropboxAuthToken"
                        class="col-md-12"
                        :field="form.dropboxAuthToken"
                    >
                        <template #label>
                            {{ $gettext('Access Code') }}
                        </template>
                    </b-wrapped-form-group>
                </div>
            </b-form-group>
        </b-card-body>
    </section>
</template>

<script setup>
import BWrappedFormGroup from "~/components/Form/BWrappedFormGroup.vue";
import {computed} from "vue";

const props = defineProps({
    form: {
        type: Object,
        required: true
    }
});

const baseAuthUrl = 'https://www.dropbox.com/oauth2/authorize';

const authUrl = computed(() => {
    const params = new URLSearchParams();
    params.append('client_id', props.form.dropboxAppKey.$model);
    params.append('response_type', 'code');
    params.append('token_access_type', 'offline');

    return baseAuthUrl + '?' + params.toString();
});
</script>
