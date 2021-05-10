<template>
    <b-modal size="lg" id="edit_modal" ref="modal" :title="langTitle" :busy="loading">
        <b-spinner v-if="loading">
        </b-spinner>

        <b-form class="form" v-else @submit.prevent="doSubmit">
            <b-form-group>
                <b-row>
                    <b-form-group class="col-md-6" label-for="form_edit_title">
                        <template v-slot:label>
                            <translate key="lang_form_edit_title">Episode</translate>
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
                            <translate key="lang_form_edit_link_desc">Typically a website with content about the episode.</translate>
                        </template>
                        <b-form-input id="form_edit_link" type="text" v-model="$v.form.link.$model"
                                    :state="$v.form.link.$dirty ? !$v.form.link.$error : null"></b-form-input>
                    </b-form-group>

                    <b-form-group class="col-md-12" label-for="form_edit_description">
                        <template v-slot:label>
                            <translate key="lang_form_edit_description">Description</translate>
                        </template>
                        <template v-slot:description>
                            <translate key="lang_form_edit_description_desc">The description of the episode. The typical maximum amount of text allowed for this is 4000 characters.</translate>
                        </template>
                        <b-form-textarea id="form_edit_description" v-model="$v.form.description.$model"
                                    :state="$v.form.description.$dirty ? !$v.form.description.$error : null"></b-form-textarea>
                    </b-form-group>

                    <b-form-group class="col-md-6" label-for="form_edit_publish_date">
                        <template v-slot:label>
                            <translate key="lang_form_edit_publish_date">Publish Date</translate>
                        </template>
                        <template v-slot:description>
                            <translate key="lang_form_edit_publish_date_desc">The date when the episode should be published.</translate>
                        </template>
                        <b-form-datepicker id="form_edit_publish_date" v-model="$v.form.publish_date.$model"
                                    :state="$v.form.publish_date.$dirty ? !$v.form.publish_date.$error : null" :locale="locale"></b-form-datepicker>
                    </b-form-group>

                    <b-form-group class="col-md-6" label-for="form_edit_publish_time">
                        <template v-slot:label>
                            <translate key="lang_form_edit_publish_time">Publish Time</translate>
                        </template>
                        <template v-slot:description>
                            <translate key="lang_form_edit_publish_time_desc">The time when the episode should be published (according to the stations timezone).</translate>
                        </template>
                        <b-form-timepicker id="form_edit_publish_time" v-model="$v.form.publish_time.$model"
                                    :state="$v.form.publish_time.$dirty ? !$v.form.publish_time.$error : null" :locale="locale"></b-form-timepicker>
                    </b-form-group>

                    <b-form-group class="col-md-12" label-for="form_edit_explicit">
                        <template v-slot:description>
                            <translate key="lang_form_edit_explicit_desc">Indicates the presence of explicit content (explicit language or adult content). Apple Podcasts displays an Explicit parental advisory graphic for your episode if turned on. Episodes containing explicit material aren’t available in some Apple Podcasts territories.</translate>
                        </template>
                        <b-form-checkbox id="form_edit_explicit" v-model="$v.form.explicit.$model">
                            <translate key="lang_form_edit_explicit">Contains explicit content</translate>
                        </b-form-checkbox>
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
                <b-button variant="danger" type="button" @click="clearArtwork(artworkSrc)">
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
            locale: String,
            podcastId: Number
        },
        data () {
            return {
                loading: true,
                editUrl: null,
                hasCustomArtwork: false,
                artworkSrc: null,
                form: {}
            };
        },
        computed: {
            langTitle () {
                return this.isEditMode
                    ? this.$gettext('Edit Episode')
                    : this.$gettext('Add Episode');
            },
            isEditMode () {
                return this.editUrl !== null;
            }
        },
        validations: {
            form: {
                'title': { required },
                'link': {},
                'description': { required },
                'publish_date': {},
                'publish_time': {},
                'explicit': {},
                'artwork_file': {}
            }
        },
        methods: {
            updatePreviewArtwork (file) {
                if (!(file instanceof File)) {
                    return;
                }
                let vueThis = this;
                let fileReader = new FileReader();
                fileReader.addEventListener('load', function () {
                    vueThis.artworkSrc = fileReader.result;
                }, false);
                fileReader.readAsDataURL(file);
            },
            resetForm () {
                this.form = {
                    'title': '',
                    'link': '',
                    'description': '',
                    'publish_date': '',
                    'publish_time': '',
                    'explicit': false,
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

                    let publishDate = '';
                    let publishTime = '';

                    if (d.publishAt !== null) {
                        let publishDateTime = moment.unix(d.publishAt);
                        publishDate = publishDateTime.format('YYYY-MM-DD');
                        publishTime = publishDateTime.format('hh:mm');
                    }

                    this.form = {
                        'title': d.title,
                        'link': d.link,
                        'description': d.description,
                        'publish_date': publishDate,
                        'publish_time': publishTime,
                        'explicit': d.explicit
                    };

                    this.artworkSrc = d.artwork_src;
                    this.hasCustomArtwork = d.has_custom_artwork;

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

                let modifiedForm = this.form;
                modifiedForm.podcast_id = this.podcastId;

                if (modifiedForm.publish_date.length > 0 && modifiedForm.publish_time.length > 0) {
                    let publishDateTimeString = modifiedForm.publish_date + ' ' + modifiedForm.publish_time;
                    let publishDateTime = moment(publishDateTimeString);

                    modifiedForm.publish_at = publishDateTime.unix();
                }

                let formData = new FormData();
                Object.entries(modifiedForm).forEach(([key, value]) => {
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
                let buttonConfirmText = this.$gettext('Delete episode artwork?');

                Swal.fire({
                    title: buttonConfirmText,
                    confirmButtonText: buttonText,
                    confirmButtonColor: '#e64942',
                    showCancelButton: true,
                    focusCancel: true
                }).then((value) => {
                    if (value) {
                        axios.delete(url).then((resp) => {
                            notify('<b>' + resp.data.message + '</b>', 'success');

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
                this.resetForm();

                this.$v.form.$reset();
                this.$refs.modal.hide();
            }
        }
    };
</script>
