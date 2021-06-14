<template>
    <b-modal size="lg" id="edit_modal" ref="modal" :title="langTitle" :busy="loading">
        <b-spinner v-if="loading">
        </b-spinner>

        <b-form class="form" v-else @submit.prevent="doSubmit">
            <b-tabs content-class="mt-3">
                <podcast-form-basic-info :form="$v.form"
                                         :categories-options="categoriesOptions" :language-options="languageOptions">
                </podcast-form-basic-info>
                <podcast-common-artwork :form="$v.files" :artwork-src="record.art"></podcast-common-artwork>
            </b-tabs>

            <invisible-submit-button/>
        </b-form>
        <template v-slot:modal-footer>
            <b-button variant="default" type="button" @click="close">
                <translate key="lang_btn_close">Close</translate>
            </b-button>
            <template v-if="record.has_custom_art">
                <b-button variant="danger" type="button" @click="clearArtwork(record.links.art)">
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
import required from 'vuelidate/src/validators/required';
import InvisibleSubmitButton from '../../Common/InvisibleSubmitButton';
import BaseEditModal from '../../Common/BaseEditModal';
import PodcastFormBasicInfo from './PodcastForm/BasicInfo';
import PodcastCommonArtwork from './Common/Artwork';
import handleAxiosError from '../../Function/handleAxiosError';

export default {
    name: 'EditModal',
    components: { PodcastCommonArtwork, PodcastFormBasicInfo, InvisibleSubmitButton },
    mixins: [BaseEditModal],
    props: {
        stationTimeZone: String,
        languageOptions: Object,
        categoriesOptions: Object
    },
    data () {
        return {
            record: {
                has_custom_art: false,
                art: null,
                links: {}
            },
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
            this.record = {
                has_custom_art: false,
                art: null,
                links: {}
            };
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
        populateForm (d) {
            this.record = d;
            this.form = {
                'title': d.title,
                'link': d.link,
                'description': d.description,
                'language': d.language,
                'categories': d.categories
            };
        },
        buildSubmitRequest () {
            let formData = new FormData();
            formData.append('body', JSON.stringify(this.form));
            Object.entries(this.files).forEach(([key, value]) => {
                if (null !== value) {
                    formData.append(key, value);
                }
            });

            return {
                method: 'POST',
                url: (this.isEditMode)
                    ? this.editUrl
                    : this.createUrl,
                data: formData,
                headers: {
                    'Content-Type': 'multipart/form-data'
                }
            };
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
                        handleAxiosError(err);
                    });
                }
            });
        }
    }
};
</script>
