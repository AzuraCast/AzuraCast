<template>
    <div class="row g-3">
        <form-markup
            id="form_song_length"
            :label="$gettext('Song Length')"
        >
            {{ songLength }}
        </form-markup>

        <form-group-field
            id="edit_form_amplify"
            class="col-md-6"
            :field="form.amplify"
            input-type="number"
            :input-attrs="{ step: '0.1' }"
            :label="$gettext('Amplify: Amplification (dB)')"
            :description="$gettext('The volume in decibels to amplify the track with. Leave blank to use the system default.')"
        />

        <form-group-field
            id="edit_form_fade_overlap"
            class="col-md-6"
            :field="form.fade_overlap"
            input-type="number"
            :input-attrs="{ step: '0.1' }"
            :label="$gettext('Custom Fading: Overlap Time (seconds)')"
            :description="$gettext('The time that this song should overlap its surrounding songs when fading. Leave blank to use the system default.')"
        />

        <form-group-field
            id="edit_form_fade_in"
            class="col-md-6"
            :field="form.fade_in"
            input-type="number"
            :input-attrs="{ step: '0.1' }"
            :label="$gettext('Custom Fading: Fade-In Time (seconds)')"
            :description="$gettext('The time period that the song should fade in. Leave blank to use the system default.')"
        />

        <form-group-field
            id="edit_form_fade_out"
            class="col-md-6"
            :field="form.fade_out"
            input-type="number"
            :input-attrs="{ step: '0.1' }"
            :label="$gettext('Custom Fading: Fade-Out Time (seconds)')"
            :description="$gettext('The time period that the song should fade out. Leave blank to use the system default.')"
        />

        <form-group-field
            id="edit_form_cue_in"
            class="col-md-6"
            :field="form.cue_in"
            input-type="number"
            :input-attrs="{ step: '0.1' }"
            :label="$gettext('Custom Cues: Cue-In Point (seconds)')"
            :description="$gettext('Seconds from the start of the song that the AutoDJ should start playing.')"
        />

        <form-group-field
            id="edit_form_cue_out"
            class="col-md-6"
            :field="form.cue_out"
            input-type="number"
            :input-attrs="{ step: '0.1' }"
            :label="$gettext('Custom Cues: Cue-Out Point (seconds)')"
            :description="$gettext('Seconds from the start of the song that the AutoDJ should stop playing.')"
        />
    </div>
</template>

<script setup lang="ts">
import FormGroupField from "~/components/Form/FormGroupField.vue";
import FormMarkup from "~/components/Form/FormMarkup.vue";

const props = defineProps({
    form: {
        type: Object,
        required: true
    },
    songLength: {
        type: String,
        required: true
    }
});
</script>
