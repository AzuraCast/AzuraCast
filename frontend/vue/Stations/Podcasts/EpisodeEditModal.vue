<template>
    <b-modal size="lg" id="edit_modal" ref="modal" :title="langTitle" :busy="loading">
        <b-spinner v-if="loading">
        </b-spinner>

        <b-form class="form" v-else @submit.prevent="doSubmit">
            <b-tabs content-class="mt-3">
                <episode-form-basic-info :form="$v.form"></episode-form-basic-info>
                <episode-form-media v-model="$v.form.media_file.$model" :record-has-media="record.has_media"
                                    :new-media-url="newMediaUrl" :edit-media-url="record.links.media"
                                    :download-url="record.links.download"></episode-form-media>
                <podcast-common-artwork v-model="$v.form.artwork_file.$model" :artwork-src="record.art"
                                        :new-art-url="newArtUrl" :edit-art-url="record.links.art"></podcast-common-artwork>
            </b-tabs>

            <invisible-submit-button/>
        </b-form>
        <template #modal-footer>
            <b-button variant="default" type="button" @click="close">
                <translate key="lang_btn_close">Close</translate>
            </b-button>
            <b-button variant="primary" type="submit" @click="doSubmit" :disabled="$v.form.$invalid">
                {{ langSaveChanges }}
            </b-button>
        </template>
    </b-modal>
</template>

<script>
import required from 'vuelidate/src/validators/required';
import InvisibleSubmitButton from '../../Common/InvisibleSubmitButton';
import BaseEditModal from '../../Common/BaseEditModal';
import EpisodeFormBasicInfo from './EpisodeForm/BasicInfo';
import PodcastCommonArtwork from './Common/Artwork';
import EpisodeFormMedia from './EpisodeForm/Media';

export default {
    name: 'EditModal',
    components: { EpisodeFormMedia, PodcastCommonArtwork, EpisodeFormBasicInfo, InvisibleSubmitButton },
    mixins: [BaseEditModal],
    props: {
        stationTimeZone: String,
        locale: String,
        podcastId: String,
        newArtUrl: String,
        newMediaUrl: String
    },
    data () {
        return {
            uploadPercentage: null,
            record: {
                has_custom_art: false,
                art: null,
                has_media: false,
                media: null,
                links: {},
                artwork_file: null,
                media_file: null
            },
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
            this.uploadPercentage = null;
            this.record = {
                has_custom_art: false,
                art: null,
                has_media: false,
                media: null,
                links: {
                    art: null,
                    media: null
                }
            };
            this.form = {
                'title': '',
                'link': '',
                'description': '',
                'publish_date': '',
                'publish_time': '',
                'explicit': false,
                'artwork_file': null,
                'media_file': null
            };
        },
        populateForm (d) {
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
        },
        buildSubmitRequest () {
            let modifiedForm = this.form;
            if (modifiedForm.publish_date.length > 0 && modifiedForm.publish_time.length > 0) {
                let publishDateTimeString = modifiedForm.publish_date + ' ' + modifiedForm.publish_time;
                let publishDateTime = moment(publishDateTimeString);

                modifiedForm.publish_at = publishDateTime.unix();
            }

            return {
                method: (this.isEditMode)
                    ? 'PUT'
                    : 'POST',
                url: (this.isEditMode)
                    ? this.editUrl
                    : this.createUrl,
                data: this.form
            };
        }
    }
};
</script>
