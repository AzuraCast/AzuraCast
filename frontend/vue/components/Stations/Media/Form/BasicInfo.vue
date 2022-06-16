<template>
    <b-form-group>
        <b-form-row>
            <b-wrapped-form-group class="col-md-6" id="edit_form_path" :field="form.path">
                <template #label="{lang}">
                    <translate :key="lang">File Name</translate>
                </template>
                <template #description="{lang}">
                        <translate
                            :key="lang">The relative path of the file in the station's media directory.</translate>
                </template>
            </b-wrapped-form-group>

            <b-wrapped-form-group class="col-md-6" id="edit_form_title" :field="form.title">
                <template #label="{lang}">
                    <translate :key="lang">Song Title</translate>
                </template>
            </b-wrapped-form-group>

            <b-wrapped-form-group class="col-md-6" id="edit_form_artist" :field="form.artist">
                <template #label="{lang}">
                    <translate :key="lang">Song Artist</translate>
                </template>
            </b-wrapped-form-group>

            <b-wrapped-form-group class="col-md-6" id="edit_form_genre" :field="form.genre">
                <template #label="{lang}">
                    <translate :key="lang">Song Genre</translate>
                </template>
            </b-wrapped-form-group>

            <b-wrapped-form-group class="col-md-6" id="edit_form_album" :field="form.album">
                <template #label="{lang}">
                    <translate :key="lang">Song Album</translate>
                </template>
            </b-wrapped-form-group>

            <b-wrapped-form-group class="col-md-6" id="edit_form_lyrics" :field="form.lyrics" input-type="textarea">
                <template #label="{lang}">
                    <translate :key="lang">Song Lyrics</translate>
                </template>
            </b-wrapped-form-group>

            <b-wrapped-form-group class="col-md-6" id="edit_form_isrc" :field="form.isrc">
                <template #label="{lang}">
                    <translate :key="lang">ISRC</translate>
                </template>
                <template #description="{lang}">
                        <translate
                            :key="lang">International Standard Recording Code, used for licensing reports.</translate>
                </template>
            </b-wrapped-form-group>
        </b-form-row>
    </b-form-group>
</template>

<script>
import BWrappedFormGroup from "~/components/Form/BWrappedFormGroup";

export default {
    name: 'MediaFormBasicInfo',
    components: {BWrappedFormGroup},
    props: {
        form: Object
    }
};
</script>
