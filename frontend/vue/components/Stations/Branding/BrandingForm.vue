<template>
    <form
        class="form vue-form"
        @submit.prevent="submit"
    >
        <section
            class="card mb-3"
            role="region"
        >
            <div class="card-header bg-primary-dark">
                <h2 class="card-title">
                    {{ $gettext('Branding Settings') }}
                </h2>
            </div>

            <b-alert
                variant="danger"
                :show="error != null"
            >
                {{ error }}
            </b-alert>

            <b-overlay
                variant="card"
                :show="loading"
            >
                <div class="card-body">
                    <b-form-group>
                        <div class="form-row">
                            <b-wrapped-form-group
                                id="form_edit_default_album_art_url"
                                class="col-md-6"
                                :field="v$.default_album_art_url"
                            >
                                <template #label>
                                    {{ $gettext('Default Album Art URL') }}
                                </template>
                                <template #description>
                                    {{
                                        $gettext('If a song has no album art, this URL will be listed instead. Leave blank to use the standard placeholder art.')
                                    }}
                                </template>
                            </b-wrapped-form-group>

                            <b-wrapped-form-group
                                id="edit_form_public_custom_css"
                                class="col-md-12"
                                :field="v$.public_custom_css"
                            >
                                <template #label>
                                    {{ $gettext('Custom CSS for Public Pages') }}
                                </template>
                                <template #description>
                                    {{
                                        $gettext('This CSS will be applied to the station public pages.')
                                    }}
                                </template>
                                <template #default="slotProps">
                                    <codemirror-textarea
                                        :id="slotProps.id"
                                        v-model="slotProps.field.$model"
                                        mode="css"
                                    />
                                </template>
                            </b-wrapped-form-group>

                            <b-wrapped-form-group
                                id="edit_form_public_custom_js"
                                class="col-md-12"
                                :field="v$.public_custom_js"
                            >
                                <template #label>
                                    {{ $gettext('Custom JS for Public Pages') }}
                                </template>
                                <template #description>
                                    {{
                                        $gettext('This javascript code will be applied to the station public pages.')
                                    }}
                                </template>
                                <template #default="slotProps">
                                    <codemirror-textarea
                                        :id="slotProps.id"
                                        v-model="slotProps.field.$model"
                                        mode="javascript"
                                    />
                                </template>
                            </b-wrapped-form-group>
                        </div>

                        <b-button
                            size="lg"
                            type="submit"
                            class="mt-3"
                            variant="primary"
                        >
                            {{ $gettext('Save Changes') }}
                        </b-button>
                    </b-form-group>
                </div>
            </b-overlay>
        </section>
    </form>
</template>

<script setup>
import CodemirrorTextarea from "~/components/Common/CodemirrorTextarea.vue";
import BWrappedFormGroup from "~/components/Form/BWrappedFormGroup.vue";
import {onMounted, ref} from "vue";
import {useAxios} from "~/vendor/axios";
import mergeExisting from "~/functions/mergeExisting";
import {useNotify} from "~/vendor/bootstrapVue";
import {useTranslate} from "~/vendor/gettext";
import {useVuelidateOnForm} from "~/functions/useVuelidateOnForm";

const props = defineProps({
    profileEditUrl: {
        type: String,
        required: true
    },
});

const loading = ref(true);
const error = ref(null);

const {form, resetForm, v$, ifValid} = useVuelidateOnForm(
    {
        'default_album_art_url': {},
        'public_custom_css': {},
        'public_custom_js': {},
    },
    {
        'default_album_art_url': '',
        'public_custom_css': '',
        'public_custom_js': ''
    }
);

const {$gettext} = useTranslate();
const {axios} = useAxios();

const populateForm = (data) => {
    form.value = mergeExisting(form.value, data);
};

const relist = () => {
    resetForm();

    loading.value = true;

    axios.get(props.profileEditUrl).then((resp) => {
        populateForm(resp.data.branding_config);
        loading.value = false;
    });
}

onMounted(relist);

const {wrapWithLoading, notifySuccess} = useNotify();

const submit = () => {
    ifValid(() => {
        wrapWithLoading(
            axios({
                method: 'PUT',
                url: props.profileEditUrl,
                data: {
                    branding_config: form.value
                }
            })
        ).then(() => {
            notifySuccess();
            relist();
        });
    });
}
</script>
