<template>
    <tab :label="$gettext('Basic Information')"
         :item-header-class="tabClass">
        <div class="row g-3">
            <form-group-field
                id="edit_form_path"
                class="col-md-6"
                :field="r$.path"
                :label="$gettext('File Name')"
                :description="$gettext('The relative path of the file in the station\'s media directory.')"
            />

            <form-group-field
                id="edit_form_title"
                class="col-md-6"
                :field="r$.title"
                :label="$gettext('Song Title')"
            />

            <form-group-field
                id="edit_form_artist"
                class="col-md-6"
                :field="r$.artist"
                :label="$gettext('Song Artist')"
            />

            <form-group-field
                id="edit_form_genre"
                class="col-md-6"
                :field="r$.genre"
                :label="$gettext('Song Genre')"
            />

            <form-group-field
                id="edit_form_album"
                class="col-md-6"
                :field="r$.album"
                :label="$gettext('Song Album')"
            />

            <form-group-field
                id="edit_form_lyrics"
                class="col-md-6"
                :field="r$.lyrics"
                input-type="textarea"
                :label="$gettext('Song Lyrics')"
            />

            <form-group-field
                id="edit_form_isrc"
                class="col-md-6"
                :field="r$.isrc"
                :label="$gettext('ISRC')"
                :description="$gettext('International Standard Recording Code, used for licensing reports.')"
            />
        </div>
    </tab>
</template>

<script setup lang="ts">
import FormGroupField from "~/components/Form/FormGroupField.vue";
import {storeToRefs} from "pinia";
import {useFormTabClass} from "~/functions/useFormTabClass.ts";
import {computed} from "vue";
import {useStationsMediaForm} from "~/components/Stations/Media/Form/form.ts";
import Tab from "~/components/Common/Tab.vue";

const {r$} = storeToRefs(useStationsMediaForm());

const tabClass = useFormTabClass(computed(() => r$.value.$groups.basicInfoTab));
</script>
