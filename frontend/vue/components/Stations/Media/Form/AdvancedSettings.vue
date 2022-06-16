<template>
    <b-form-group>
        <b-form-row>
            <b-form-group class="col-md-6" label-for="edit_form_song_length">
                <template #label>
                    <translate key="lang_song_length">Song Length</translate>
                </template>

                <b-form-input id="edit_form_song_length" readonly :value="songLength"></b-form-input>
            </b-form-group>

            <b-wrapped-form-group class="col-md-6" id="edit_form_amplify" :field="form.amplify"
                                  input-type="number" :input-attrs="{ step: '0.1' }">
                <template #label="{lang}">
                    <translate :key="lang">Amplify: Amplification (dB)</translate>
                </template>
                <template #description="{lang}">
                    <translate :key="lang">The volume in decibels to amplify the track with. Leave blank to use the system default.</translate>
                </template>
            </b-wrapped-form-group>

            <b-wrapped-form-group class="col-md-6" id="edit_form_fade_overlap" :field="form.fade_overlap"
                                  input-type="number" :input-attrs="{ step: '0.1' }">
                <template #label="{lang}">
                    <translate :key="lang">Custom Fading: Overlap Time (seconds)</translate>
                </template>
                <template #description="{lang}">
                    <translate :key="lang">The time that this song should overlap its surrounding songs when fading. Leave blank to use the system default.</translate>
                </template>
            </b-wrapped-form-group>

            <b-wrapped-form-group class="col-md-6" id="edit_form_fade_in" :field="form.fade_in"
                                  input-type="number" :input-attrs="{ step: '0.1' }">
                <template #label="{lang}">
                    <translate :key="lang">Custom Fading: Fade-In Time (seconds)</translate>
                </template>
                <template #description="{lang}">
                    <translate :key="lang">The time period that the song should fade in. Leave blank to use the system default.</translate>
                </template>
            </b-wrapped-form-group>

            <b-wrapped-form-group class="col-md-6" id="edit_form_fade_out" :field="form.fade_out"
                                  input-type="number" :input-attrs="{ step: '0.1' }">
                <template #label="{lang}">
                    <translate :key="lang">Custom Fading: Fade-Out Time (seconds)</translate>
                </template>
                <template #description="{lang}">
                    <translate :key="lang">The time period that the song should fade out. Leave blank to use the system default.</translate>
                </template>
            </b-wrapped-form-group>

            <b-wrapped-form-group class="col-md-6" id="edit_form_cue_in" :field="form.cue_in"
                                  input-type="number" :input-attrs="{ step: '0.1' }">
                <template #label="{lang}">
                    <translate :key="lang">Custom Cues: Cue-In Point (seconds)</translate>
                </template>
                <template #description="{lang}">
                    <translate
                        :key="lang">Seconds from the start of the song that the AutoDJ should start playing.</translate>
                </template>
            </b-wrapped-form-group>

            <b-wrapped-form-group class="col-md-6" id="edit_form_cue_out" :field="form.cue_out"
                                  input-type="number" :input-attrs="{ step: '0.1' }">
                <template #label="{lang}">
                    <translate :key="lang">Custom Cues: Cue-Out Point (seconds)</translate>
                </template>
                <template #description="{lang}">
                    <translate
                        :key="lang">Seconds from the start of the song that the AutoDJ should stop playing.</translate>
                </template>
            </b-wrapped-form-group>
        </b-form-row>
    </b-form-group>
</template>

<script>
import BWrappedFormGroup from "~/components/Form/BWrappedFormGroup";

export default {
    name: 'MediaFormAdvancedSettings',
    components: {BWrappedFormGroup},
    props: {
        form: Object,
        songLength: String
    },
};
</script>
