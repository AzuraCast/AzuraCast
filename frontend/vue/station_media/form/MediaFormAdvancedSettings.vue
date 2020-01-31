<template>
    <b-tab :title="langTitle">
        <b-form-group>
            <b-row>
                <b-form-group class="col-md-6" label-for="edit_form_song_length">
                    <template v-slot:label>
                        <translate>Song Length</translate>
                    </template>

                    <b-form-input id="edit_form_song_length" readonly :value="songLength"></b-form-input>
                </b-form-group>
                <b-form-group class="col-md-6" label-for="edit_form_amplify">
                    <template v-slot:label>
                        <translate>Amplify: Amplification (dB)</translate>
                    </template>
                    <template v-slot:description>
                        <translate>The volume in decibels to amplify the track with. Leave blank to use the system default.</translate>
                    </template>
                    <b-form-input type="text" id="edit_form_amplify"
                                  v-model="form.amplify.$model"
                                  :state="form.amplify.$dirty ? !form.amplify.$error : null"></b-form-input>
                </b-form-group>
                <b-form-group class="col-md-6" label-for="edit_form_fade_overlap">
                    <template v-slot:label>
                        <translate>Custom Fading: Overlap Time (seconds)</translate>
                    </template>
                    <template v-slot:description>
                        <translate>The time that this song should overlap its surrounding songs when fading. Leave blank to use the system default.</translate>
                    </template>
                    <b-form-input type="text" id="edit_form_fade_overlap"
                                  v-model="form.fade_overlap.$model"
                                  :state="form.fade_overlap.$dirty ? !form.fade_overlap.$error : null"></b-form-input>
                </b-form-group>
                <b-form-group class="col-md-6" label-for="edit_form_fade_in">
                    <template v-slot:label>
                        <translate>Custom Fading: Fade-In Time (seconds)</translate>
                    </template>
                    <template v-slot:description>
                        <translate>The time period that the song should fade in. Leave blank to use the system default.</translate>
                    </template>
                    <b-form-input type="text" id="edit_form_fade_in"
                                  v-model="form.fade_in.$model"
                                  :state="form.fade_in.$dirty ? !form.fade_in.$error : null"></b-form-input>
                </b-form-group>
                <b-form-group class="col-md-6" label-for="edit_form_fade_out">
                    <template v-slot:label>
                        <translate>Custom Fading: Fade-Out Time (seconds)</translate>
                    </template>
                    <template v-slot:description>
                        <translate>The time period that the song should fade out. Leave blank to use the system default.</translate>
                    </template>
                    <b-form-input type="text" id="edit_form_fade_out"
                                  v-model="form.fade_out.$model"
                                  :state="form.fade_out.$dirty ? !form.fade_out.$error : null"></b-form-input>
                </b-form-group>
                <b-form-group class="col-md-6" label-for="edit_form_cue_in">
                    <template v-slot:label>
                        <translate>Custom Cues: Cue-In Point (seconds)</translate>
                    </template>
                    <template v-slot:description>
                        <translate>Seconds from the start of the song that the AutoDJ should start playing.</translate>
                    </template>
                    <b-form-input type="text" id="edit_form_cue_in"
                                  v-model="form.cue_in.$model"
                                  :state="form.cue_in.$dirty ? !form.cue_in.$error : null"></b-form-input>
                </b-form-group>
                <b-form-group class="col-md-6" label-for="edit_form_cue_out">
                    <template v-slot:label>
                        <translate>Custom Cues: Cue-Out Point (seconds)</translate>
                    </template>
                    <template v-slot:description>
                        <translate>Seconds from the start of the song that the AutoDJ should stop playing.</translate>
                    </template>
                    <b-form-input type="text" id="edit_form_cue_out"
                                  v-model="form.cue_out.$model"
                                  :state="form.cue_out.$dirty ? !form.cue_out.$error : null"></b-form-input>
                </b-form-group>
            </b-row>
        </b-form-group>
    </b-tab>
</template>

<script>
    export default {
        name: 'MediaFormAdvancedSettings',
        props: {
            form: Object,
            songLength: String
        },
        computed: {
            langTitle () {
                return this.$gettext('Advanced Playback Settings');
            }
        }
    };
</script>