<template>
    <b-form-group>
        <div class="row g-3">
            <b-form-group
                class="col-md-6"
                label-for="edit_form_song_length"
            >
                <template #label>
                    {{ $gettext('Song Length') }}
                </template>

                <b-form-input
                    id="edit_form_song_length"
                    readonly
                    :value="songLength"
                />
            </b-form-group>

            <form-group-field
                id="edit_form_amplify"
                class="col-md-6"
                :field="form.amplify"
                input-type="number"
                :input-attrs="{ step: '0.1' }"
            >
                <template #label>
                    {{ $gettext('Amplify: Amplification (dB)') }}
                </template>
                <template #description>
                    {{
                        $gettext('The volume in decibels to amplify the track with. Leave blank to use the system default.')
                    }}
                </template>
            </form-group-field>

            <form-group-field
                id="edit_form_fade_overlap"
                class="col-md-6"
                :field="form.fade_overlap"
                input-type="number"
                :input-attrs="{ step: '0.1' }"
            >
                <template #label>
                    {{ $gettext('Custom Fading: Overlap Time (seconds)') }}
                </template>
                <template #description>
                    {{
                        $gettext('The time that this song should overlap its surrounding songs when fading. Leave blank to use the system default.')
                    }}
                </template>
            </form-group-field>

            <form-group-field
                id="edit_form_fade_in"
                class="col-md-6"
                :field="form.fade_in"
                input-type="number"
                :input-attrs="{ step: '0.1' }"
            >
                <template #label>
                    {{ $gettext('Custom Fading: Fade-In Time (seconds)') }}
                </template>
                <template #description>
                    {{
                        $gettext('The time period that the song should fade in. Leave blank to use the system default.')
                    }}
                </template>
            </form-group-field>

            <form-group-field
                id="edit_form_fade_out"
                class="col-md-6"
                :field="form.fade_out"
                input-type="number"
                :input-attrs="{ step: '0.1' }"
            >
                <template #label>
                    {{ $gettext('Custom Fading: Fade-Out Time (seconds)') }}
                </template>
                <template #description>
                    {{
                        $gettext('The time period that the song should fade out. Leave blank to use the system default.')
                    }}
                </template>
            </form-group-field>

            <form-group-field
                id="edit_form_cue_in"
                class="col-md-6"
                :field="form.cue_in"
                input-type="number"
                :input-attrs="{ step: '0.1' }"
            >
                <template #label>
                    {{ $gettext('Custom Cues: Cue-In Point (seconds)') }}
                </template>
                <template #description>
                    {{ $gettext('Seconds from the start of the song that the AutoDJ should start playing.') }}
                </template>
            </form-group-field>

            <form-group-field
                id="edit_form_cue_out"
                class="col-md-6"
                :field="form.cue_out"
                input-type="number"
                :input-attrs="{ step: '0.1' }"
            >
                <template #label>
                    {{ $gettext('Custom Cues: Cue-Out Point (seconds)') }}
                </template>
                <template #description>
                    {{ $gettext('Seconds from the start of the song that the AutoDJ should stop playing.') }}
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
    },
    songLength: {
        type: String,
        required: true
    }
});
</script>
