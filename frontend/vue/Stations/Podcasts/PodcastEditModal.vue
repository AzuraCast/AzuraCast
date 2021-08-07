<template>
    <b-modal size="lg" id="edit_modal" ref="modal" :title="langTitle" :busy="loading">
        <b-spinner v-if="loading">
        </b-spinner>

        <b-form class="form" v-else @submit.prevent="doSubmit">
            <b-tabs content-class="mt-3">
                <podcast-form-basic-info :form="$v.form"
                                         :categories-options="categoriesOptions" :language-options="languageOptions">
                </podcast-form-basic-info>
                <podcast-common-artwork v-model="$v.form.artwork_file.$model" :artwork-src="record.art"
                                        :new-art-url="newArtUrl" :edit-art-url="record.links.art"></podcast-common-artwork>
            </b-tabs>

            <invisible-submit-button/>
        </b-form>
        <template v-slot:modal-footer>
            <b-button variant="default" type="button" @click="close">
                <translate key="lang_btn_close">Close</translate>
            </b-button>
            <b-button variant="primary" type="submit" @click="doSubmit" :disabled="$v.form.$invalid">
                <translate key="lang_btn_save_changes">Save Changes</translate>
            </b-button>
        </template>
    </b-modal>
</template>

<script>
import required from 'vuelidate/src/validators/required';
import InvisibleSubmitButton from '../../Common/InvisibleSubmitButton';
import BaseEditModal from '../../Common/BaseEditModal';
import PodcastFormBasicInfo from './PodcastForm/BasicInfo';
import PodcastCommonArtwork from './Common/Artwork';

export default {
    name: 'EditModal',
    components: { PodcastCommonArtwork, PodcastFormBasicInfo, InvisibleSubmitButton },
    mixins: [BaseEditModal],
    props: {
        stationTimeZone: String,
        languageOptions: Object,
        categoriesOptions: Object,
        newArtUrl: String
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
                'author': '',
                'email': '',
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
        }
    },
    validations: {
        form: {
            'title': { required },
            'link': {},
            'description': {},
            'language': { required },
            'author': {},
            'email': {},
            'categories': { required },
            'artwork_file': {}
        }
    },
    methods: {
        resetForm () {
            this.record = {
                has_custom_art: false,
                art: null,
                links: {
                    art: null
                }
            };
            this.form = {
                'title': '',
                'link': '',
                'description': '',
                'language': 'en',
                'author': '',
                'email': '',
                'categories': [],
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
                'author': d.author,
                'email': d.email,
                'categories': d.categories,
                'artwork_file': null
            };
        }
    }
};
</script>
