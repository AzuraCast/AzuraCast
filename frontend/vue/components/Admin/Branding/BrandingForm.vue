<template>
    <form
        class="form vue-form"
        @submit.prevent="submit"
    >
        <section
            class="card mb-3"
            role="region"
        >
            <div class="card-header text-bg-primary">
                <h2 class="card-title">
                    {{ $gettext('Branding Settings') }}
                </h2>
            </div>

            <div
                v-show="error != null"
                class="alert alert-danger"
            >
                {{ error }}
            </div>

            <div class="card-body">
                <loading :loading="isLoading">
                    <div class="row g-3">
                        <form-group-multi-check
                            id="edit_form_public_theme"
                            class="col-md-6"
                            :field="v$.public_theme"
                            :options="publicThemeOptions"
                            stacked
                            radio
                            :label="$gettext('Base Theme for Public Pages')"
                            :description="$gettext('Select a theme to use as a base for station public pages and the login page.')"
                        />

                        <div class="col-md-6">
                            <form-group-checkbox
                                id="form_edit_hide_album_art"
                                class="mb-2"
                                :field="v$.hide_album_art"
                                :label="$gettext('Hide Album Art on Public Pages')"
                                :description="$gettext('If selected, album art will not display on public-facing radio pages.')"
                            />

                            <form-group-checkbox
                                id="form_edit_hide_product_name"
                                :field="v$.hide_product_name"
                                :label="$gettext('Hide AzuraCast Branding on Public Pages')"
                                :description="$gettext('If selected, this will remove the AzuraCast branding from public-facing pages.')"
                            />
                        </div>

                        <form-group-field
                            id="form_edit_homepage_redirect_url"
                            class="col-md-6"
                            :field="v$.homepage_redirect_url"
                            :label="$gettext('Homepage Redirect URL')"
                            :description="$gettext('If a visitor is not signed in and visits the AzuraCast homepage, you can automatically redirect them to the URL specified here. Leave blank to redirect them to the login screen by default.')"
                        />

                        <form-group-field
                            id="form_edit_default_album_art_url"
                            class="col-md-6"
                            :field="v$.default_album_art_url"
                            :label="$gettext('Default Album Art URL')"
                            :description="$gettext('If a song has no album art, this URL will be listed instead. Leave blank to use the standard placeholder art.')"
                        />

                        <form-group-field
                            id="edit_form_public_custom_css"
                            class="col-md-12"
                            :field="v$.public_custom_css"
                            :label="$gettext('Custom CSS for Public Pages')"
                            :description="$gettext('This CSS will be applied to the station public pages and login page.')"
                        >
                            <template #default="slotProps">
                                <codemirror-textarea
                                    :id="slotProps.id"
                                    v-model="slotProps.field.$model"
                                    mode="css"
                                />
                            </template>
                        </form-group-field>

                        <form-group-field
                            id="edit_form_public_custom_js"
                            class="col-md-12"
                            :field="v$.public_custom_js"
                            :label="$gettext('Custom JS for Public Pages')"
                            :description="$gettext('This javascript code will be applied to the station public pages and login page.')"
                        >
                            <template #default="slotProps">
                                <codemirror-textarea
                                    :id="slotProps.id"
                                    v-model="slotProps.field.$model"
                                    mode="javascript"
                                />
                            </template>
                        </form-group-field>

                        <form-group-field
                            id="edit_form_internal_custom_css"
                            class="col-md-12"
                            :field="v$.internal_custom_css"
                            :label="$gettext('Custom CSS for Internal Pages')"
                            :description="$gettext('This CSS will be applied to the main management pages, like this one.')"
                        >
                            <template #default="slotProps">
                                <codemirror-textarea
                                    :id="slotProps.id"
                                    v-model="slotProps.field.$model"
                                    mode="css"
                                />
                            </template>
                        </form-group-field>
                    </div>

                    <button
                        class="btn btn-primary mt-3"
                        type="submit"
                    >
                        {{ $gettext('Save Changes') }}
                    </button>
                </loading>
            </div>
        </section>
    </form>
</template>

<script setup>
import CodemirrorTextarea from "~/components/Common/CodemirrorTextarea.vue";
import FormGroupField from "~/components/Form/FormGroupField.vue";
import FormGroupCheckbox from "~/components/Form/FormGroupCheckbox.vue";
import {computed, onMounted, ref} from "vue";
import {useAxios} from "~/vendor/axios";
import mergeExisting from "~/functions/mergeExisting";
import {useNotify} from "~/functions/useNotify";
import {useTranslate} from "~/vendor/gettext";
import {useVuelidateOnForm} from "~/functions/useVuelidateOnForm";
import FormGroupMultiCheck from "~/components/Form/FormGroupMultiCheck.vue";
import Loading from "~/components/Common/Loading.vue";

const props = defineProps({
    apiUrl: {
        type: String,
        required: true
    },
});

const isLoading = ref(true);
const error = ref(null);

const {form, resetForm, v$, ifValid} = useVuelidateOnForm(
    {
        'public_theme': {},
        'hide_album_art': {},
        'homepage_redirect_url': {},
        'default_album_art_url': {},
        'hide_product_name': {},
        'public_custom_css': {},
        'public_custom_js': {},
        'internal_custom_css': {}
    },
    {
        'public_theme': '',
        'hide_album_art': false,
        'homepage_redirect_url': '',
        'default_album_art_url': '',
        'hide_product_name': false,
        'public_custom_css': '',
        'public_custom_js': '',
        'internal_custom_css': ''
    }
);

const {$gettext} = useTranslate();

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
    resetForm();

    isLoading.value = true;

    axios.get(props.apiUrl).then((resp) => {
        populateForm(resp.data);
        isLoading.value = false;
    });
}

onMounted(relist);

const {wrapWithLoading, notifySuccess} = useNotify();

const submit = () => {
    ifValid(() => {
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
    });
}
</script>
