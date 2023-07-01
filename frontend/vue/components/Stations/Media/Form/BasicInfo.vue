<template>
    <b-form-group>
        <div class="row g-3">
            <form-group-field
                id="edit_form_path"
                class="col-md-6"
                :field="form.path"
            >
                <template #label>
                    {{ $gettext('File Name') }}
                </template>
                <template #description>
                    {{ $gettext('The relative path of the file in the station\'s media directory.') }}
                </template>
            </form-group-field>

            <form-group-field
                id="edit_form_title"
                class="col-md-6"
                :field="form.title"
            >
                <template #label>
                    {{ $gettext('Song Title') }}
                </template>
            </form-group-field>

            <form-group-field
                id="edit_form_artist"
                class="col-md-6"
                :field="form.artist"
            >
                <template #label>
                    {{ $gettext('Song Artist') }}
                </template>
            </form-group-field>

            <form-group-field
                id="edit_form_genre"
                class="col-md-6"
                :field="form.genre"
            >
                <template #label>
                    {{ $gettext('Song Genre') }}
                </template>
            </form-group-field>

            <form-group-field
                id="edit_form_album"
                class="col-md-6"
                :field="form.album"
            >
                <template #label>
                    {{ $gettext('Song Album') }}
                </template>
            </form-group-field>

            <form-group-field
                id="edit_form_lyrics"
                class="col-md-6"
                :field="form.lyrics"
                input-type="textarea"
            >
                <template #label>
                    {{ $gettext('Song Lyrics') }}
                </template>
            </form-group-field>

            <form-group-field
                id="edit_form_isrc"
                class="col-md-6"
                :field="form.isrc"
            >
                <template #label>
                    {{ $gettext('ISRC') }}
                </template>
                <template #description>
                    {{ $gettext('International Standard Recording Code, used for licensing reports.') }}
                </template>
            </form-group-field>
        </div>
    </b-form-group>
</template>

<script setup>
import FormGroupField from "~/components/Form/FormGroupField";

const props = defineProps({
    form: {
        type: Object,
        required: true
    }
});
</script>
