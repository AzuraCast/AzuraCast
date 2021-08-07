<template>
    <b-tab :title="langTitle">
        <b-form-group>
            <b-row>
                <b-col md="8">
                    <b-form-group label-for="edit_form_art">
                        <template #label>
                            <translate key="artwork_file">Select PNG/JPG artwork file</translate>
                        </template>
                        <template #description>
                            <translate key="artwork_file_desc">Artwork must be a minimum size of 1400 x 1400 pixels and a maximum size of 3000 x 3000 pixels for Apple Podcasts.</translate>
                        </template>
                        <b-form-file id="edit_form_art" accept="image/jpeg, image/png"
                                     @input="uploadNewArt"></b-form-file>
                    </b-form-group>
                </b-col>
                <b-col md="4" v-if="src">
                    <b-img :src="src" :alt="langTitle" rounded fluid></b-img>

                    <div class="buttons pt-3">
                        <b-button block variant="danger" @click="deleteArt">
                            <translate key="lang_btn_delete_art">Clear Artwork</translate>
                        </b-button>
                    </div>
                </b-col>
            </b-row>
        </b-form-group>
    </b-tab>
</template>

<script>
import axios from 'axios';
import handleAxiosError from '../../../Function/handleAxiosError';

export default {
    name: 'PodcastCommonArtwork',
    props: {
        value: Object,
        artworkSrc: String,
        editArtUrl: String,
        newArtUrl: String
    },
    data () {
        return {
            src: this.artworkSrc
        };
    },
    computed: {
        langTitle () {
            return this.$gettext('Artwork');
        }
    },
    methods: {
        uploadNewArt (file) {
            if (!(file instanceof File)) {
                return;
            }

            let fileReader = new FileReader();
            fileReader.addEventListener('load', () => {
                this.src = fileReader.result;
            }, false);
            fileReader.readAsDataURL(file);

            let url = (this.editArtUrl) ? this.editArtUrl : this.newArtUrl;
            let formData = new FormData();
            formData.append('art', file);

            axios.post(url, formData).then((resp) => {
                this.$emit('input', resp.data);
            }).catch((err) => {
                handleAxiosError(err);
            });
        },
        deleteArt () {
            if (this.editArtUrl) {
                axios.delete(this.editArtUrl).then((resp) => {
                    this.src = null;
                }).catch((err) => {
                    handleAxiosError(err);
                });
            } else {
                this.src = null;
            }
        }
    }
};
</script>
