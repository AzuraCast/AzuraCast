<template>
    <b-form-group>
        <b-row>
            <b-col md="4">
                <b-img :src="albumArtSrc" rounded fluid></b-img>
                <br>
                <b-button block variant="link" class="text-danger mt-2" @click="deleteArt">
                    {{ $gettext('Delete Album Art') }}
                </b-button>
            </b-col>
            <b-col md="8">
                <b-form-group label-for="edit_form_art">
                    <template #label>
                        {{ $gettext('Replace Album Cover Art') }}
                    </template>
                    <b-form-file id="edit_form_art" v-model="artFile" accept="image/*"
                                 @input="uploadNewArt"></b-form-file>
                </b-form-group>
            </b-col>
        </b-row>
    </b-form-group>
</template>

<script>

export default {
    name: 'MediaFormAlbumArt',
    props: {
        albumArtUrl: String
    },
    data() {
        return {
            artFile: null,
            albumArtSrc: null
        };
    },
    watch: {
        albumArtUrl: {
            immediate: true,
            handler(newVal) {
                this.albumArtSrc = newVal;
            }
        }
    },
    methods: {
        uploadNewArt() {
            if (null === this.artFile) {
                return;
            }

            let formData = new FormData();
            formData.append('art', this.artFile);

            this.axios.post(this.albumArtUrl, formData).then(() => {
                this.reloadArt();
            }).catch((err) => {
                console.log(err);
                this.reloadArt();
            });
        },
        deleteArt() {
            this.axios.delete(this.albumArtUrl).then(() => {
                this.reloadArt();
            }).catch((err) => {
                console.log(err);
                this.reloadArt();
            });
        },
        reloadArt() {
            this.artFile = null;
            this.albumArtSrc = this.albumArtUrl + '?' + Math.floor(Date.now() / 1000);
        }
    }
};
</script>
