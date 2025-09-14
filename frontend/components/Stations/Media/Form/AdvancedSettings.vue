<template>
    <tab :label="$gettext('Advanced')"
         :item-header-class="tabClass">
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
                :field="r$.extra_metadata.amplify"
                input-type="number"
                :input-attrs="{ step: 'any' }"
                :label="$gettext('Amplify: Amplification (dB)')"
                :description="$gettext('The volume in decibels to amplify the track with. Leave blank to use the system default.')"
                clearable
            />

            <form-group-field
                id="edit_form_cross_start_next"
                class="col-md-6"
                :field="r$.extra_metadata.cross_start_next"
                input-type="number"
                :input-attrs="{ step: 'any' }"
                :label="$gettext('Custom Fading: Start Next (seconds)')"
                :description="$gettext('Seconds from the start of the song that the next song should begin when fading. Leave blank to use the system default.')"
                clearable
            />

            <form-group-field
                id="edit_form_fade_in"
                class="col-md-6"
                :field="r$.extra_metadata.fade_in"
                input-type="number"
                :input-attrs="{ step: 'any' }"
                :label="$gettext('Custom Fading: Fade-In Time (seconds)')"
                :description="$gettext('The time period that the song should fade in. Leave blank to use the system default.')"
                clearable
            />

            <form-group-field
                id="edit_form_fade_out"
                class="col-md-6"
                :field="r$.extra_metadata.fade_out"
                input-type="number"
                :input-attrs="{ step: 'any' }"
                :label="$gettext('Custom Fading: Fade-Out Time (seconds)')"
                :description="$gettext('The time period that the song should fade out. Leave blank to use the system default.')"
                clearable
            />

            <form-group-field
                id="edit_form_cue_in"
                class="col-md-6"
                :field="r$.extra_metadata.cue_in"
                input-type="number"
                :input-attrs="{ step: 'any' }"
                :label="$gettext('Custom Cues: Cue-In Point (seconds)')"
                :description="$gettext('Seconds from the start of the song that the AutoDJ should start playing.')"
                clearable
            />

            <form-group-field
                id="edit_form_cue_out"
                class="col-md-6"
                :field="r$.extra_metadata.cue_out"
                input-type="number"
                :input-attrs="{ step: 'any' }"
                :label="$gettext('Custom Cues: Cue-Out Point (seconds)')"
                :description="$gettext('Seconds from the start of the song that the AutoDJ should stop playing.')"
                clearable
            />
        </div>
    </tab>
</template>

<script setup lang="ts">
import FormGroupField from "~/components/Form/FormGroupField.vue";
import FormMarkup from "~/components/Form/FormMarkup.vue";
import {storeToRefs} from "pinia";
import {useStationsMediaForm} from "~/components/Stations/Media/Form/form.ts";
import {useFormTabClass} from "~/functions/useFormTabClass.ts";
import {computed} from "vue";
import Tab from "~/components/Common/Tab.vue";

defineProps<{
    songLength: string,
}>();

const {r$} = storeToRefs(useStationsMediaForm());

const tabClass = useFormTabClass(computed(() => r$.value.$groups.advancedSettingsTab));
</script>
