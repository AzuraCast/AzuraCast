<template>
    <b-modal size="lg" id="edit_modal" ref="modal" :title="langTitle" :busy="loading">
        <b-spinner v-if="loading">
        </b-spinner>

        <b-form class="form" v-else @submit.prevent="doSubmit">
            <b-tabs content-class="mt-3">
                <episode-form-basic-info :form="$v.form"></episode-form-basic-info>
                <episode-form-media :form="$v.form" :media="media" :download-url="downloadUrl"></episode-form-media>
                <podcast-common-artwork :form="$v.form" :artwork-src="artworkSrc"></podcast-common-artwork>
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
            media: {},
            editUrl: null,
            downloadUrl: null,
            clearArtUrl: null,
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
            'artwork_file': {},
            'media_file': {}
        }
    },
    methods: {
        resetForm () {
            this.media = {};
            this.editUrl = null;
            this.downloadUrl = null;
            this.clearArtUrl = null;
            this.artworkSrc = null;

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

                this.downloadUrl = d.links.download;
                this.clearArtUrl = d.links.art;
                this.artworkSrc = d.art;
                this.media = d.media;

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

            this.$v.form.$reset();
            this.$refs.modal.hide();
        }
    }
};
</script>
