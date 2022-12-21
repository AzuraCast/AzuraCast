<template>
    <b-form-group>
        <div class="form-row">
            <b-wrapped-form-group class="col-md-6" id="edit_form_path" :field="form.path">
                <template #label>
                    {{ $gettext('File Name') }}
                </template>
                <template #description>
                    {{ $gettext('The relative path of the file in the station\'s media directory.') }}
                </template>
            </b-wrapped-form-group>

            <b-wrapped-form-group class="col-md-6" id="edit_form_title" :field="form.title">
                <template #label>
                    {{ $gettext('Song Title') }}
                </template>
            </b-wrapped-form-group>

            <b-wrapped-form-group class="col-md-6" id="edit_form_artist" :field="form.artist">
                <template #label>
                    {{ $gettext('Song Artist') }}
                </template>
            </b-wrapped-form-group>

            <b-wrapped-form-group class="col-md-6" id="edit_form_genre" :field="form.genre">
                <template #label>
                    {{ $gettext('Song Genre') }}
                </template>
            </b-wrapped-form-group>

            <b-wrapped-form-group class="col-md-6" id="edit_form_album" :field="form.album">
                <template #label>
                    {{ $gettext('Song Album') }}
                </template>
            </b-wrapped-form-group>

            <b-wrapped-form-group class="col-md-6" id="edit_form_lyrics" :field="form.lyrics" input-type="textarea">
                <template #label>
                    {{ $gettext('Song Lyrics') }}
                </template>
            </b-wrapped-form-group>

            <b-wrapped-form-group class="col-md-6" id="edit_form_isrc" :field="form.isrc">
                <template #label>
                    {{ $gettext('ISRC') }}
                </template>
                <template #description>
                    {{ $gettext('International Standard Recording Code, used for licensing reports.') }}
                </template>
            </b-wrapped-form-group>
        </div>
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
