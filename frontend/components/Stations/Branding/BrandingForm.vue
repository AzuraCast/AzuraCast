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
                            :field="r$.offline_text"
                            :label="$gettext('Station Offline Display Text')"
                            :description="$gettext('This will be shown on public player pages if the station is offline. Leave blank to default to a localized version of &quot;%{message}&quot;.', {message: $gettext('Station Offline')})"
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
                            :description="$gettext('This CSS will be applied to the station public pages.')"
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
                            :description="$gettext('This javascript code will be applied to the station public pages.')"
                        >
                            <template #default="{id, model}">
                                <codemirror-textarea
                                    :id="id"
                                    v-model="model.$model"
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
import {useNotify} from "~/components/Common/Toasts/useNotify.ts";
import {useTranslate} from "~/vendor/gettext";
import Loading from "~/components/Common/Loading.vue";
import {useResettableRef} from "~/functions/useResettableRef.ts";
import {useAppRegle} from "~/vendor/regle.ts";
import {StationBrandingConfiguration} from "~/entities/ApiInterfaces.ts";

const props = defineProps<{
    profileEditUrl: string
}>();

const isLoading = ref(true);
const error = ref(null);

type Row = Required<StationBrandingConfiguration>;

const {record: form, reset: resetForm} = useResettableRef<Row>({
    default_album_art_url: "",
    public_custom_css: "",
    public_custom_js: "",
    offline_text: ""
});

const {r$} = useAppRegle(
    form,
    {
        default_album_art_url: {},
        public_custom_css: {},
        public_custom_js: {},
        offline_text: {}
    },
    {}
);

const {$gettext} = useTranslate();
const {axios} = useAxios();

const populateForm = (data: typeof form.value) => {
    form.value = mergeExisting(form.value, data);
};

const relist = async () => {
    resetForm();
    r$.$reset();

    isLoading.value = true;

    const {data} = await axios.get(props.profileEditUrl);

    populateForm(data.branding_config);
    isLoading.value = false;
}

onMounted(relist);

const {notifySuccess} = useNotify();

const submit = async () => {
    const {valid} = await r$.$validate();
    if (!valid) {
        return;
    }

    await axios({
        method: 'PUT',
        url: props.profileEditUrl,
        data: {
            branding_config: form.value
        }
    });

    notifySuccess();
    await relist();
}
</script>
