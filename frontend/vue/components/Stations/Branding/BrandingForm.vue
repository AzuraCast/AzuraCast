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

            <loading :loading="isLoading">
                <div class="card-body">
                    <div class="row g-3">
                        <form-group-field
                            id="form_edit_offline_text"
                            class="col-md-6"
                            :field="v$.offline_text"
                            :label="$gettext('Station Offline Display Text')"
                            :description="$gettext('This will be shown on public player pages if the station is offline. Leave blank to default to a localized version of &quot;%{message}&quot;.', {message: $gettext('Station Offline')})"
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
                            :description="$gettext('This CSS will be applied to the station public pages.')"
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
                            :description="$gettext('This javascript code will be applied to the station public pages.')"
                        >
                            <template #default="slotProps">
                                <codemirror-textarea
                                    :id="slotProps.id"
                                    v-model="slotProps.field.$model"
                                    mode="javascript"
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
                </div>
            </loading>
        </section>
    </form>
</template>

<script setup lang="ts">
import CodemirrorTextarea from "~/components/Common/CodemirrorTextarea.vue";
import FormGroupField from "~/components/Form/FormGroupField.vue";
import {onMounted, ref} from "vue";
import {useAxios} from "~/vendor/axios";
import mergeExisting from "~/functions/mergeExisting";
import {useNotify} from "~/functions/useNotify";
import {useTranslate} from "~/vendor/gettext";
import {useVuelidateOnForm} from "~/functions/useVuelidateOnForm";
import Loading from "~/components/Common/Loading.vue";

const props = defineProps({
    profileEditUrl: {
        type: String,
        required: true
    },
});

const isLoading = ref(true);
const error = ref(null);

const {form, resetForm, v$, ifValid} = useVuelidateOnForm(
    {
        default_album_art_url: {},
        public_custom_css: {},
        public_custom_js: {},
        offline_text: {}
    },
    {
        default_album_art_url: '',
        public_custom_css: '',
        public_custom_js: '',
        offline_text: ''
    }
);

const {$gettext} = useTranslate();
const {axios} = useAxios();

const populateForm = (data) => {
    form.value = mergeExisting(form.value, data);
};

const relist = () => {
    resetForm();

    isLoading.value = true;

    axios.get(props.profileEditUrl).then((resp) => {
        populateForm(resp.data.branding_config);
        isLoading.value = false;
    });
}

onMounted(relist);

const {notifySuccess} = useNotify();

const submit = () => {
    ifValid(() => {
        axios({
            method: 'PUT',
            url: props.profileEditUrl,
            data: {
                branding_config: form.value
            }
        }).then(() => {
            notifySuccess();
            relist();
        });
    });
}
</script>
