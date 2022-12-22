<template>
    <form class="form vue-form" @submit.prevent="submit">
        <section class="card mb-3" role="region">
            <div class="card-header bg-primary-dark">
                <h2 class="card-title">
                    {{ $gettext('Branding Settings') }}
                </h2>
            </div>

            <b-alert variant="danger" :show="error != null">{{ error }}</b-alert>

            <b-overlay variant="card" :show="loading">
                <div class="card-body">
                    <b-form-group>
                        <div class="form-row">
                            <b-wrapped-form-group class="col-md-6" id="edit_form_public_theme"
                                                  :field="v$.public_theme">
                                <template #label>
                                    {{ $gettext('Base Theme for Public Pages') }}
                                </template>
                                <template #description>
                                    {{
                                        $gettext('Select a theme to use as a base for station public pages and the login page.')
                                    }}
                                </template>
                                <template #default="props">
                                    <b-form-radio-group stacked :id="props.id" :options="publicThemeOptions"
                                                        v-model="props.field.$model">
                                    </b-form-radio-group>
                                </template>
                            </b-wrapped-form-group>

                            <b-col md="6">
                                <b-wrapped-form-checkbox class="mb-2" id="form_edit_hide_album_art"
                                                         :field="v$.hide_album_art">
                                    <template #label>
                                        {{ $gettext('Hide Album Art on Public Pages') }}
                                    </template>
                                    <template #description>
                                        {{
                                            $gettext('If selected, album art will not display on public-facing radio pages.')
                                        }}
                                    </template>
                                </b-wrapped-form-checkbox>

                                <b-wrapped-form-checkbox id="form_edit_hide_product_name"
                                                         :field="v$.hide_product_name">
                                    <template #label>
                                        {{ $gettext('Hide AzuraCast Branding on Public Pages') }}
                                    </template>
                                    <template #description>
                                        {{
                                            $gettext('If selected, this will remove the AzuraCast branding from public-facing pages.')
                                        }}
                                    </template>
                                </b-wrapped-form-checkbox>
                            </b-col>

                            <b-wrapped-form-group class="col-md-6" id="form_edit_homepage_redirect_url"
                                                  :field="v$.homepage_redirect_url">
                                <template #label>
                                    {{ $gettext('Homepage Redirect URL') }}
                                </template>
                                <template #description>
                                    {{
                                        $gettext('If a visitor is not signed in and visits the AzuraCast homepage, you can automatically redirect them to the URL specified here. Leave blank to redirect them to the login screen by default.')
                                    }}
                                </template>
                            </b-wrapped-form-group>

                            <b-wrapped-form-group class="col-md-6" id="form_edit_default_album_art_url"
                                                  :field="v$.default_album_art_url">
                                <template #label>
                                    {{ $gettext('Default Album Art URL') }}
                                </template>
                                <template #description>
                                    {{
                                        $gettext('If a song has no album art, this URL will be listed instead. Leave blank to use the standard placeholder art.')
                                    }}
                                </template>
                            </b-wrapped-form-group>

                            <b-wrapped-form-group class="col-md-12" id="edit_form_public_custom_css"
                                                  :field="v$.public_custom_css">
                                <template #label>
                                    {{ $gettext('Custom CSS for Public Pages') }}
                                </template>
                                <template #description>
                                    {{
                                        $gettext('This CSS will be applied to the station public pages and login page.')
                                    }}
                                </template>
                                <template #default="props">
                                    <codemirror-textarea :id="props.id" mode="css"
                                                         v-model="props.field.$model"></codemirror-textarea>
                                </template>
                            </b-wrapped-form-group>

                            <b-wrapped-form-group class="col-md-12" id="edit_form_public_custom_js"
                                                  :field="v$.public_custom_js">
                                <template #label>
                                    {{ $gettext('Custom JS for Public Pages') }}
                                </template>
                                <template #description>
                                    {{
                                        $gettext('This javascript code will be applied to the station public pages and login page.')
                                    }}
                                </template>
                                <template #default="props">
                                    <codemirror-textarea :id="props.id" mode="javascript"
                                                         v-model="props.field.$model"></codemirror-textarea>
                                </template>
                            </b-wrapped-form-group>

                            <b-wrapped-form-group class="col-md-12" id="edit_form_internal_custom_css"
                                                  :field="v$.internal_custom_css">
                                <template #label>
                                    {{ $gettext('Custom CSS for Internal Pages') }}
                                </template>
                                <template #description>
                                    {{
                                        $gettext('This CSS will be applied to the main management pages, like this one.')
                                    }}
                                </template>
                                <template #default="props">
                                    <codemirror-textarea :id="props.id" mode="css"
                                                         v-model="props.field.$model"></codemirror-textarea>
                                </template>
                            </b-wrapped-form-group>
                        </div>

                        <b-button size="lg" type="submit" class="mt-3" variant="primary">
                            {{ $gettext('Save Changes') }}
                        </b-button>
                    </b-form-group>
                </div>
            </b-overlay>
        </section>
    </form>
</template>

<script setup>
import useVuelidate from "@vuelidate/core";
import CodemirrorTextarea from "~/components/Common/CodemirrorTextarea";
import BWrappedFormGroup from "~/components/Form/BWrappedFormGroup";
import BWrappedFormCheckbox from "~/components/Form/BWrappedFormCheckbox";
import {computed, onMounted, ref} from "vue";
import gettext from "~/vendor/gettext";
import {useAxios} from "~/vendor/axios";
import mergeExisting from "~/functions/mergeExisting";
import {useNotify} from "~/vendor/bootstrapVue";

const props = defineProps({
    apiUrl: String,
});

const loading = ref(true);
const error = ref(null);

const blankForm = {
    'public_theme': '',
    'hide_album_art': false,
    'homepage_redirect_url': '',
    'default_album_art_url': '',
    'hide_product_name': false,
    'public_custom_css': '',
    'public_custom_js': '',
    'internal_custom_css': ''
};

const form = ref({...blankForm});

const validations = {
    'public_theme': {},
    'hide_album_art': {},
    'homepage_redirect_url': {},
    'default_album_art_url': {},
    'hide_product_name': {},
    'public_custom_css': {},
    'public_custom_js': {},
    'internal_custom_css': {}
};

const v$ = useVuelidate(validations, form);

const {$gettext} = gettext;

const publicThemeOptions = computed(() => {
    return [
        {
            text: $gettext('Prefer System Default'),
            value: 'browser',
        },
        {
            text: $gettext('Light'),
            value: 'light',
        },
        {
            text: $gettext('Dark'),
            value: 'dark',
        }
    ];
});

const {axios} = useAxios();

const populateForm = (data) => {
    form.value = mergeExisting(form.value, data);
};

const relist = () => {
    v$.value.$reset();
    form.value = {...blankForm};
    loading.value = true;

    axios.get(props.apiUrl).then((resp) => {
        populateForm(resp.data);
        loading.value = false;
    });
}

onMounted(relist);

const {wrapWithLoading, notifySuccess} = useNotify();

const submit = () => {
    v$.value.$touch();
    if (v$.value.$errors.length > 0) {
        return;
    }

    wrapWithLoading(
        axios({
            method: 'PUT',
            url: props.apiUrl,
            data: form.value
        })
    ).then(() => {
        notifySuccess($gettext('Changes saved.'));
        relist();
    });
}
</script>
