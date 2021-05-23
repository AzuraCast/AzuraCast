<template>
    <b-tab :title="langTitle">
        <b-form-group>
            <b-row>
                <b-form-group class="col-md-8" label-for="artwork_file">
                    <template #label>
                        <translate key="artwork_file">Select PNG/JPG artwork file</translate>
                    </template>
                    <template #description>
                        <translate key="artwork_file_desc">Artwork must be a minimum size of 1400 x 1400 pixels and a maximum size of 3000 x 3000 pixels for Apple Podcasts.</translate>
                    </template>
                    <b-form-file id="artwork_file" accept="image/jpeg, image/png" v-model="form.artwork_file.$model" @input="updatePreviewArtwork"></b-form-file>
                </b-form-group>

                <b-form-group class="col-md-4">
                    <template v-if="src">
                        <b-img fluid center :src="src" aria-hidden="true"></b-img>
                    </template>
                </b-form-group>
            </b-row>
        </b-form-group>
    </b-tab>
</template>

<script>
export default {
    name: 'PodcastCommonArtwork',
    props: {
        form: Object,
        artworkSrc: String
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
        updatePreviewArtwork (file) {
            if (!(file instanceof File)) {
                return;
            }

            let fileReader = new FileReader();
            fileReader.addEventListener('load', () => {
                this.src = fileReader.result;
            }, false);
            fileReader.readAsDataURL(file);
        }
    }
};
</script>
