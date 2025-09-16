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
                            :field="r$.public_theme"
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
                                :field="r$.hide_album_art"
                                :label="$gettext('Hide Album Art on Public Pages')"
                                :description="$gettext('If selected, album art will not display on public-facing radio pages.')"
                            />

                            <form-group-checkbox
                                id="form_edit_hide_product_name"
                                :field="r$.hide_product_name"
                                :label="$gettext('Hide AzuraCast Branding on Public Pages')"
                                :description="$gettext('If selected, this will remove the AzuraCast branding from public-facing pages.')"
                            />
                        </div>

                        <form-group-field
                            id="form_edit_homepage_redirect_url"
                            class="col-md-6"
                            :field="r$.homepage_redirect_url"
                            :label="$gettext('Homepage Redirect URL')"
                            :description="$gettext('If a visitor is not signed in and visits the AzuraCast homepage, you can automatically redirect them to the URL specified here. Leave blank to redirect them to the login screen by default.')"
                        />

                        <form-group-field
                            id="form_edit_default_album_art_url"
                            class="col-md-6"
                            :field="r$.default_album_art_url"
                            :label="$gettext('Default Album Art URL')"
                            :description="$gettext('If a song has no album art, this URL will be listed instead. Leave blank to use the standard placeholder art.')"
                        />

                        <form-group-field
                            id="edit_form_public_custom_css"
                            class="col-md-12"
                            :field="r$.public_custom_css"
                            :label="$gettext('Custom CSS for Public Pages')"
                            :description="$gettext('This CSS will be applied to the station public pages and login page.')"
                        >
                            <template #default="{id, model}">
                                <codemirror-textarea
                                    :id="id"
                                    v-model="model.$model"
                                    mode="css"
                                />
                            </template>
                        </form-group-field>

                        <form-group-field
                            id="edit_form_public_custom_js"
                            class="col-md-12"
                            :field="r$.public_custom_js"
                            :label="$gettext('Custom JS for Public Pages')"
                            :description="$gettext('This javascript code will be applied to the station public pages and login page.')"
                        >
                            <template #default="{id, model}">
                                <codemirror-textarea
                                    :id="id"
                                    v-model="model.$model"
                                    mode="javascript"
                                />
                            </template>
                        </form-group-field>

                        <form-group-field
                            id="edit_form_internal_custom_css"
                            class="col-md-12"
                            :field="r$.internal_custom_css"
                            :label="$gettext('Custom CSS for Internal Pages')"
                            :description="$gettext('This CSS will be applied to the main management pages, like this one.')"
                        >
                            <template #default="{id, model}">
                                <codemirror-textarea
                                    :id="id"
                                    v-model="model.$model"
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

<script setup lang="ts">
import CodemirrorTextarea from "~/components/Common/CodemirrorTextarea.vue";
import FormGroupField from "~/components/Form/FormGroupField.vue";
import FormGroupCheckbox from "~/components/Form/FormGroupCheckbox.vue";
import {computed, onMounted, ref} from "vue";
import {useAxios} from "~/vendor/axios";
import mergeExisting from "~/functions/mergeExisting";
import {useNotify} from "~/components/Common/Toasts/useNotify.ts";
import {useTranslate} from "~/vendor/gettext";
import FormGroupMultiCheck from "~/components/Form/FormGroupMultiCheck.vue";
import Loading from "~/components/Common/Loading.vue";
import {useAppRegle} from "~/vendor/regle.ts";
import {Settings} from "~/entities/ApiInterfaces.ts";

const props = defineProps<{
    apiUrl: string,
}>();

const isLoading = ref(true);
const error = ref(null);

type BrandingSettings = Required<Pick<Settings,
    | 'public_theme'
    | 'hide_album_art'
    | 'homepage_redirect_url'
    | 'default_album_art_url'
    | 'hide_product_name'
    | 'public_custom_css'
    | 'public_custom_js'
    | 'internal_custom_css'
>>

const blankForm: BrandingSettings = {
    public_theme: null,
    hide_album_art: false,
    homepage_redirect_url: '',
    default_album_art_url: '',
    hide_product_name: false,
    public_custom_css: '',
    public_custom_js: '',
    internal_custom_css: ''
}

const {r$} = useAppRegle(
    blankForm,
    {},
    {}
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

const relist = async () => {
    r$.$reset({
        toOriginalState: true
    });

    isLoading.value = true;

    const {data} = await axios.get(props.apiUrl);
    r$.$reset({
        toState: mergeExisting(r$.$value, data)
    });

    isLoading.value = false;
}

onMounted(relist);

const {notifySuccess} = useNotify();

const submit = async () => {
    const {valid, data: postData} = await r$.$validate();
    if (!valid) {
        return;
    }

    await axios({
        method: 'PUT',
        url: props.apiUrl,
        data: postData
    });

    notifySuccess($gettext('Changes saved.'));
    await relist();
}
</script>
