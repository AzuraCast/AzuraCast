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
                            <translate key="artwork_file_desc">This image will be used as the default album art when this streamer is live.</translate>
                        </template>
                        <b-form-file id="edit_form_art" accept="image/jpeg, image/png"
                                     @input="uploadNewArt"></b-form-file>
                    </b-form-group>
                </b-col>
                <b-col md="4" v-if="src && src !== ''">
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

export default {
    name: 'StreamersFormArtwork',
    props: {
        value: Object,
        artworkSrc: String,
        editArtUrl: String,
        newArtUrl: String,
    },
    data() {
        return {
            localSrc: null,
        };
    },
    computed: {
        langTitle() {
            return this.$gettext('Artwork');
        },
        src() {
            return this.localSrc ?? this.artworkSrc;
        }
    },
    methods: {
        uploadNewArt(file) {
            if (!(file instanceof File)) {
                return;
            }

            let fileReader = new FileReader();
            fileReader.addEventListener('load', () => {
                this.localSrc = fileReader.result;
            }, false);
            fileReader.readAsDataURL(file);

            let url = (this.editArtUrl) ? this.editArtUrl : this.newArtUrl;
            let formData = new FormData();
            formData.append('art', file);

            this.axios.post(url, formData).then((resp) => {
                this.$emit('input', resp.data);
            });
        },
        deleteArt() {
            if (this.editArtUrl) {
                this.axios.delete(this.editArtUrl).then((resp) => {
                    this.localSrc = '';
                });
            } else {
                this.localSrc = '';
            }
        }
    }
};
</script>
