<template>
    <b-modal size="lg" id="edit_modal" ref="modal" :title="langTitle" :busy="loading">
        <b-spinner v-if="loading">
        </b-spinner>

        <b-form class="form" v-else @submit.prevent="doSubmit">
            <b-tabs content-class="mt-3">
                <podcast-form-basic-info :form="$v.form"
                                         :categories-options="categoriesOptions" :language-options="languageOptions">
                </podcast-form-basic-info>
                <podcast-common-artwork :form="$v.files" :artwork-src="artworkSrc"></podcast-common-artwork>
            </b-tabs>

            <invisible-submit-button/>
        </b-form>
        <template v-slot:modal-footer>
            <b-button variant="default" type="button" @click="close">
                <translate key="lang_btn_close">Close</translate>
            </b-button>
            <b-button variant="danger" type="button" @click="clearArtwork(clearArtUrl)">
                <translate key="lang_btn_clear_artwork">Clear Art</translate>
            </b-button>
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
import PodcastFormBasicInfo from './PodcastForm/BasicInfo';
import PodcastCommonArtwork from './Common/Artwork';

export default {
    name: 'EditModal',
    components: { PodcastCommonArtwork, PodcastFormBasicInfo, InvisibleSubmitButton },
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
            form: {
                'title': '',
                'link': '',
                'description': '',
                'language': 'en',
                'categories': []
            },
            files: {
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
            'categories': { required }
        },
        files: {
            'artwork_file': {}
        }
    },
    methods: {
        resetForm () {
            this.form = {
                'title': '',
                'link': '',
                'description': '',
                'language': 'en',
                'categories': []
            };
            this.files = {
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
                formData.append('body', JSON.stringify(this.form));
                Object.entries(this.files).forEach(([key, value]) => {
                    formData.append(key, value);
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

                this.resetForm();

                this.$v.form.$reset();
                this.$refs.modal.hide();
            }
        }
    };
</script>
