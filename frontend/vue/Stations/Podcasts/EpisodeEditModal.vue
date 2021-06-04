<template>
    <b-modal size="lg" id="edit_modal" ref="modal" :title="langTitle" :busy="loading">
        <b-spinner v-if="loading">
        </b-spinner>

        <b-form class="form" v-else @submit.prevent="doSubmit">
            <b-tabs content-class="mt-3">
                <episode-form-basic-info :form="$v.form"></episode-form-basic-info>
                <episode-form-media :form="$v.files" :has-media="record.has_media" :media="record.media" :download-url="record.links.download"></episode-form-media>
                <podcast-common-artwork :form="$v.files" :artwork-src="record.art"></podcast-common-artwork>
            </b-tabs>

            <invisible-submit-button/>
        </b-form>
        <template #modal-footer>
            <b-button variant="default" type="button" @click="close">
                <translate key="lang_btn_close">Close</translate>
            </b-button>
            <template v-if="record.has_custom_art">
                <b-button variant="danger" type="button" @click="clearArtwork(record.links.art)">
                    <translate key="lang_btn_clear_artwork">Clear Art</translate>
                </b-button>
            </template>
            <b-button variant="primary" type="submit" @click="doSubmit" :disabled="$v.form.$invalid">
                {{ langSaveChanges }}
            </b-button>
        </template>
    </b-modal>
</template>

<script>
import axios from 'axios';
import { validationMixin } from 'vuelidate';
import required from 'vuelidate/src/validators/required';
import InvisibleSubmitButton from '../../Common/InvisibleSubmitButton';
import EpisodeFormBasicInfo from './EpisodeForm/BasicInfo';
import PodcastCommonArtwork from './Common/Artwork';
import EpisodeFormMedia from './EpisodeForm/Media';

export default {
    name: 'EditModal',
    components: { EpisodeFormMedia, PodcastCommonArtwork, EpisodeFormBasicInfo, InvisibleSubmitButton },
    mixins: [validationMixin],
    props: {
        createUrl: String,
        stationTimeZone: String,
        locale: String,
        podcastId: String
    },
    data () {
        return {
            loading: true,
            uploadPercentage: null,
            editUrl: null,
            record: {
                has_custom_art: false,
                art: null,
                has_media: false,
                media: null,
                links: {}
            },
            form: {},
            files: {}
        };
    },
    computed: {
        langTitle () {
            return this.isEditMode
                ? this.$gettext('Edit Episode')
                : this.$gettext('Add Episode');
        },
        langSaveChanges () {
            let baseText = this.$gettext('Save Changes');

            if (null !== this.uploadPercentage) {
                baseText = baseText + ' (' + this.uploadPercentage + '%)';
            }

            return baseText;
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
            'explicit': {}
        },
        files: {
            'artwork_file': {},
            'media_file': {}
        }
    },
    methods: {
        resetForm () {
            this.editUrl = null;
            this.uploadPercentage = null;
            this.record = {
                has_custom_art: false,
                art: null,
                has_media: false,
                media: null,
                links: {}
            };
            this.form = {
                'title': '',
                'link': '',
                'description': '',
                'publish_date': '',
                'publish_time': '',
                'explicit': false
            };
            this.files = {
                'artwork_file': null,
                'media_file': null
            };
        },
        create () {
            this.resetForm();
            this.loading = false;

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

                this.record = d;

                this.form = {
                    'title': d.title,
                    'link': d.link,
                    'description': d.description,
                    'publish_date': publishDate,
                    'publish_time': publishTime,
                    'explicit': d.explicit
                };

                this.loading = false;
            }).catch((err) => {
                console.log(err);
                this.close();
            });
        },
        doSubmit () {
            this.$v.$touch();
            if (this.$v.$anyError) {
                return;
            }

            let modifiedForm = this.form;
            if (modifiedForm.publish_date.length > 0 && modifiedForm.publish_time.length > 0) {
                let publishDateTimeString = modifiedForm.publish_date + ' ' + modifiedForm.publish_time;
                let publishDateTime = moment(publishDateTimeString);

                modifiedForm.publish_at = publishDateTime.unix();
            }

            let formData = new FormData();
            formData.append('body', JSON.stringify(modifiedForm));
            Object.entries(this.files).forEach(([key, value]) => {
                if (null !== value) {
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
                },
                onUploadProgress: (progressEvent) => {
                    this.uploadPercentage = parseInt(Math.round((progressEvent.loaded / progressEvent.total) * 100));
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
            this.resetForm();

            this.$v.$reset();
            this.$refs.modal.hide();
        }
    }
};
</script>
