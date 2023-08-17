<template>
    <div class="row g-3">
        <form-group-field
            id="edit_form_path"
            class="col-md-6"
            :field="form.path"
            :label="$gettext('File Name')"
            :description="$gettext('The relative path of the file in the station\'s media directory.')"
        />

        <form-group-field
            id="edit_form_title"
            class="col-md-6"
            :field="form.title"
            :label="$gettext('Song Title')"
        />

        <form-group-field
            id="edit_form_artist"
            class="col-md-6"
            :field="form.artist"
            :label="$gettext('Song Artist')"
        />

        <form-group-field
            id="edit_form_genre"
            class="col-md-6"
            :field="form.genre"
            :label="$gettext('Song Genre')"
        />

        <form-group-field
            id="edit_form_album"
            class="col-md-6"
            :field="form.album"
            :label="$gettext('Song Album')"
        />

        <form-group-field
            id="edit_form_lyrics"
            class="col-md-6"
            :field="form.lyrics"
            input-type="textarea"
            :label="$gettext('Song Lyrics')"
        />

        <form-group-field
            id="edit_form_isrc"
            class="col-md-6"
            :field="form.isrc"
            :label="$gettext('ISRC')"
            :description="$gettext('International Standard Recording Code, used for licensing reports.')"
        />
    </div>
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
