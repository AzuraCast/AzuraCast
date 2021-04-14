<template>
    <b-tab :title="langTitle" lazy>
        <b-form-group>
            <b-row>
                <b-col md="4">
                    <b-img :src="albumArtSrc" :alt="langTitle" rounded fluid></b-img>
                    <br>
                    <b-button block variant="link" class="text-danger mt-2" @click="deleteArt">
                        <translate key="lang_btn_delete_art">Delete Album Art</translate>
                    </b-button>
                </b-col>
                <b-col md="8">
                    <b-form-group label-for="edit_form_art">
                        <template v-slot:label>
                            <translate key="lang_btn_replace_art">Replace Album Cover Art</translate>
                        </template>
                        <b-form-file id="edit_form_art" v-model="artFile" accept="image/*"
                                     @input="uploadNewArt"></b-form-file>
                    </b-form-group>
                </b-col>
            </b-row>
        </b-form-group>
    </b-tab>
</template>

<script>
import axios from 'axios';

export default {
    name: 'MediaFormAlbumArt',
    props: {
        albumArtUrl: String
    },
    data () {
        return {
            artFile: null,
            albumArtSrc: null
        };
    },
    computed: {
        langTitle () {
            return this.$gettext('Album Art');
        }
    },
    watch: {
        albumArtUrl: {
            immediate: true,
            handler (newVal, oldVal) {
                this.albumArtSrc = newVal;
            }
        }
    },
    methods: {
        uploadNewArt () {
            let formData = new FormData();
            formData.append('art', this.artFile);

            axios.post(this.albumArtUrl, formData).then((resp) => {
                this.reloadArt();
            }).catch((err) => {
                console.log(err);
                this.reloadArt();
            });
        },
        deleteArt () {
            axios.delete(this.albumArtUrl).then((resp) => {
                this.reloadArt();
            }).catch((err) => {
                console.log(err);
                this.reloadArt();
            });
        },
        reloadArt () {
            this.artFile = null;
            this.albumArtSrc = this.albumArtUrl + '?' + Math.floor(Date.now() / 1000);
        }
    }
};
</script>
