<template>
    <b-tab :title="langTitle" active>
        <b-form-group>
            <b-row>
                <b-wrapped-form-group class="col-md-6" id="edit_form_path" :field="form.path">
                    <template #label>
                        <translate key="lang_form_path">File Name</translate>
                    </template>
                    <template #description>
                        <translate key="lang_form_path_desc">The relative path of the file in the station's media directory.</translate>
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group class="col-md-6" id="edit_form_title" :field="form.title">
                    <template #label>
                        <translate key="lang_form_title">Song Title</translate>
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group class="col-md-6" id="edit_form_artist" :field="form.artist">
                    <template #label>
                        <translate key="lang_form_artist">Song Artist</translate>
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group class="col-md-6" id="edit_form_genre" :field="form.genre">
                    <template #label>
                        <translate key="lang_form_genre">Song Genre</translate>
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group class="col-md-6" id="edit_form_album" :field="form.album">
                    <template #label>
                        <translate key="lang_form_album">Song Album</translate>
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group class="col-md-6" id="edit_form_lyrics" :field="form.lyrics">
                    <template #label>
                        <translate key="lang_form_lyrics">Song Lyrics</translate>
                    </template>
                    <template #default="props">
                        <b-form-textarea :id="props.id" v-model="props.field.$model"
                                         :state="props.state"></b-form-textarea>
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group class="col-md-6" id="edit_form_isrc" :field="form.isrc">
                    <template #label>
                        <translate key="lang_form_isrc">ISRC</translate>
                    </template>
                    <template #description>
                        <translate key="lang_form_isrc_desc">International Standard Recording Code, used for licensing reports.</translate>
                    </template>
                </b-wrapped-form-group>
            </b-row>
        </b-form-group>
    </b-tab>
</template>

<script>
import BWrappedFormGroup from "~/components/Form/BWrappedFormGroup";

export default {
    name: 'MediaFormBasicInfo',
    components: {BWrappedFormGroup},
    props: {
        form: Object
    },
    computed: {
        langTitle() {
            return this.$gettext('Basic Information');
        }
    }
};
</script>
