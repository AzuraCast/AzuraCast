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

        <form-group-field
            id="edit_form_rating"
            class="col-md-6"
            :field="form.rating"
            input-type="number"
            :input-attrs="{min: 0, max: 5, step: 1}"
            :label="$gettext('Star Rating')"
            :description="$gettext('Rate this song from 0 to 5 stars.')"
        />

        <div class="col-md-6">
            <div class="form-group">
                <label class="form-label">{{ $gettext('Favorite') }}</label>
                <div class="form-check">
                    <input
                        :id="form.is_favorite.$name"
                        v-model="form.is_favorite.$model"
                        type="checkbox"
                        class="form-check-input"
                        style="width: 1.25rem; height: 1.25rem;"
                    >
                    <label :for="form.is_favorite.$name" class="form-check-label ms-2">
                        {{ $gettext('Mark this song as a favorite.') }}
                    </label>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import FormGroupField from "~/components/Form/FormGroupField.vue";
import {VuelidateObject} from "~/functions/useVuelidateOnForm.ts";

defineProps<{
    form: VuelidateObject
}>();
</script>
