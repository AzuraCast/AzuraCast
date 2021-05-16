<template>
    <b-modal size="lg" id="edit_modal" ref="modal" :title="langTitle" :busy="loading">
        <b-spinner v-if="loading">
        </b-spinner>

        <b-form class="form" v-else @submit.prevent="doSubmit">
            <b-form-group>
                <b-row>
                    <b-form-group class="col-md-6" label-for="form_edit_title">
                        <template v-slot:label>
                            <translate key="lang_form_edit_title">Podcast Title</translate>
                        </template>
                        <b-form-input id="form_edit_title" type="text" v-model="$v.form.title.$model"
                                    :state="$v.form.title.$dirty ? !$v.form.title.$error : null"></b-form-input>
                        <b-form-invalid-feedback>
                            <translate key="lang_error_required">This field is required.</translate>
                        </b-form-invalid-feedback>
                    </b-form-group>

                    <b-form-group class="col-md-6" label-for="form_edit_link">
                        <template v-slot:label>
                            <translate key="lang_form_edit_link">Website</translate>
                        </template>
                        <template v-slot:description>
                            <translate key="lang_form_edit_link_desc">Typically the home page of a podcast.</translate>
                        </template>
                        <b-form-input id="form_edit_link" type="text" v-model="$v.form.link.$model"
                                    :state="$v.form.link.$dirty ? !$v.form.link.$error : null"></b-form-input>
                    </b-form-group>

                    <b-form-group class="col-md-12" label-for="form_edit_description">
                        <template v-slot:label>
                            <translate key="lang_form_edit_description">Description</translate>
                        </template>
                        <template v-slot:description>
                            <translate key="lang_form_edit_description_desc">The description of your podcast. The typical maximum amount of text allowed for this is 4000 characters.</translate>
                        </template>
                        <b-form-textarea id="form_edit_description" v-model="$v.form.description.$model"
                                    :state="$v.form.description.$dirty ? !$v.form.description.$error : null"></b-form-textarea>
                    </b-form-group>

                    <b-form-group class="col-md-12" label-for="form_edit_language">
                        <template v-slot:label>
                            <translate key="lang_form_edit_language">Language</translate>
                        </template>
                        <template v-slot:description>
                            <translate key="lang_form_edit_language_desc">The language spoken on the podcast.</translate>
                        </template>
                        <b-form-select id="form_edit_language" v-model="$v.form.language.$model" :options="languageOptions"
                                   :state="$v.form.language.$dirty ? !$v.form.language.$error : null"></b-form-select>
                        <b-form-invalid-feedback>
                            <translate key="lang_error_required">This field is required.</translate>
                        </b-form-invalid-feedback>
                    </b-form-group>

                    <b-form-group class="col-md-12" label-for="form_edit_categories">
                        <template v-slot:label>
                            <translate key="lang_form_edit_categories">Categories</translate>
                        </template>
                        <template v-slot:description>
                            <translate key="lang_form_edit_categories_desc">Select the category/categories that best reflects the content of your podcast.</translate>
                        </template>
                        <b-form-select id="form_edit_categories" v-model="$v.form.categories.$model" :options="categoriesOptions"
                                   :state="$v.form.categories.$dirty ? !$v.form.categories.$error : null" multiple></b-form-select>
                        <b-form-invalid-feedback>
                            <translate key="lang_error_required">This field is required.</translate>
                        </b-form-invalid-feedback>
                    </b-form-group>

                    <b-form-group class="col-md-8" label-for="artwork_file">
                        <template v-slot:label>
                            <translate key="artwork_file">Select PNG/JPG artwork file</translate>
                        </template>
                        <template v-slot:description>
                            <translate key="artwork_file_desc">Artwork must be a minimum size of 1400 x 1400 pixels and a maximum size of 3000 x 3000 pixels for Apple Podcasts.</translate>
                        </template>
                        <b-form-file id="artwork_file" accept="image/jpeg, image/png" v-model="$v.form.artwork_file.$model" @input="updatePreviewArtwork"></b-form-file>
                    </b-form-group>

                    <b-form-group class="col-md-4">
                        <template v-if="artworkSrc">
                            <b-img fluid center v-bind:src="artworkSrc" aria-hidden="true"></b-img>
                        </template>
                    </b-form-group>
                </b-row>
            </b-form-group>

            <invisible-submit-button/>
        </b-form>
        <template v-slot:modal-footer>
            <b-button variant="default" type="button" @click="close">
                <translate key="lang_btn_close">Close</translate>
            </b-button>
            <template v-if="hasCustomArtwork">
                <b-button variant="danger" type="button" @click="clearArtwork(clearArtUrl)">
                    <translate key="lang_btn_clear_artwork">Clear Art</translate>
                </b-button>
            </template>
            <b-button variant="primary" type="submit" @click="doSubmit" :disabled="$v.form.$invalid">
                <translate key="lang_btn_save_changes">Save Changes</translate>
            </b-button>
        </template>
    </b-modal>
</template>

<script>
import axios from 'axios';
import { validationMixin } from 'vuelidate';
import required from 'vuelidate/src/validators/required';
import InvisibleSubmitButton from '../../Common/InvisibleSubmitButton';

export default {
    name: 'EditModal',
    components: { InvisibleSubmitButton },
    mixins: [validationMixin],
    props: {
        createUrl: String,
        stationTimeZone: String,
        languageOptions: Object,
        categoriesOptions: Object
    },
    data () {
        return {
            loading: true,
            editUrl: null,
            artworkSrc: null,
            clearArtUrl: null,
            hasCustomArtwork: false,
            form: {
                'title': '',
                'link': '',
                'description': '',
                'language': 'en',
                'categories': [],
                'artwork_file': null
            }
        };
    },
    computed: {
        langTitle () {
            return this.isEditMode
                ? this.$gettext('Edit Podcast')
                : this.$gettext('Add Podcast');
        },
        isEditMode () {
            return this.editUrl !== null;
        }
    },
    validations: {
        form: {
            'title': { required },
            'link': {},
            'description': {},
            'language': { required },
            'categories': { required },
            'artwork_file': {}
        }
    },
    methods: {
        updatePreviewArtwork (file) {
            if (!(file instanceof File)) {
                return;
            }

            let fileReader = new FileReader();
            fileReader.addEventListener('load', () => {
                this.artworkSrc = fileReader.result;
            }, false);
            fileReader.readAsDataURL(file);
        },
        resetForm () {
            this.form = {
                'title': '',
                'link': '',
                'description': '',
                'language': 'en',
                'categories': [],
                'artwork_file': null
            };
        },
        create () {
            this.resetForm();
            this.loading = false;
            this.editUrl = null;

            this.$refs.modal.show();
        },
        edit (recordUrl) {
            this.resetForm();
            this.loading = true;
            this.editUrl = recordUrl;
            this.$refs.modal.show();

            axios.get(this.editUrl).then((resp) => {
                let d = resp.data;

                this.form = {
                    'title': d.title,
                    'link': d.link,
                    'description': d.description,
                    'language': d.language,
                    'categories': d.categories
                };

                this.clearArtUrl = d.links.art;
                this.artworkSrc = d.art;
                this.hasCustomArtwork = d.has_custom_art;

                this.loading = false;
            }).catch((err) => {
                console.log(err);
                this.close();
            });
            },
            doSubmit () {
                this.$v.form.$touch();
                if (this.$v.form.$anyError) {
                    return;
                }

                let formData = new FormData();
                Object.entries(this.form).forEach(([key, value]) => {
                    if (Array.isArray(value)) {
                        value.forEach(arrayValue => {
                            formData.append(`${key}[]`, arrayValue);
                        });
                    } else {
                        formData.append(key, value);
                    }
                });

                axios({
                    method: 'POST',
                    url: (this.isEditMode)
                        ? this.editUrl
                        : this.createUrl,
                    data: formData,
                    headers: {
                        'Content-Type': 'multipart/form-data'
                    }
                }).then((resp) => {
                    let notifyMessage = this.$gettext('Changes saved.');
                    notify('<b>' + notifyMessage + '</b>', 'success', false);

                    this.$emit('relist');
                    this.close();
                }).catch((err) => {
                    console.error(err);

                    let notifyMessage = this.$gettext('An error occurred and your request could not be completed.');
                    notify('<b>' + notifyMessage + '</b>', 'danger', false);

                    this.$emit('relist');
                    this.close();
                });
            },
            clearArtwork (url) {
                let buttonText = this.$gettext('Remove Artwork');
                let buttonConfirmText = this.$gettext('Delete podcast artwork?');

                Swal.fire({
                    title: buttonConfirmText,
                    confirmButtonText: buttonText,
                    confirmButtonColor: '#e64942',
                    showCancelButton: true,
                    focusCancel: true
                }).then((result) => {
                    if (result.value) {
                        axios.delete(url).then((resp) => {
                            notify('<b>' + resp.data.message + '</b>', 'success');

                            this.$emit('relist');
                            this.close();
                        }).catch((err) => {
                            console.error(err);
                            if (err.response.message) {
                                notify('<b>' + err.response.message + '</b>', 'danger');
                            }
                        });
                    }
                });
            },
            close () {
                this.loading = false;
                this.editUrl = null;
                this.clearArtUrl = null;
                this.artworkSrc = null;
                this.hasCustomArtwork = false;

                this.resetForm();

                this.$v.form.$reset();
                this.$refs.modal.hide();
            }
        }
    };
</script>
