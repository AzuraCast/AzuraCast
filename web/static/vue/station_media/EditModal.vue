<template>
    <b-modal size="lg" id="edit_modal" ref="modal" :title="langTitle" :busy="loading">
        <b-spinner v-if="loading">
        </b-spinner>
        <b-form v-else @submit.prevent="doEdit">
            <b-tabs content-class="mt-3">
                <b-tab :title="langBasicInfoTab" active>
                    <b-row>
                        <b-col md="6">
                            <b-form-group label-for="edit_form_path">
                                <template v-slot:label v-translate>
                                    File Name
                                </template>
                                <template v-slot:description v-translate>
                                    The relative path of the file in the station's media directory.
                                </template>
                                <b-form-input type="text" id="edit_form_path" v-model="$v.form.path.$model"
                                              :state="$v.form.path.$dirty ? !$v.form.path.$error : null"></b-form-input>
                                <b-form-invalid-feedback v-translate>
                                    This field is required.
                                </b-form-invalid-feedback>
                            </b-form-group>
                        </b-col>
                        <b-col md="6">
                            <b-form-group label-for="edit_form_title">
                                <template v-slot:label v-translate>
                                    Song Title
                                </template>
                                <b-form-input type="text" id="edit_form_title" v-model="$v.form.title.$model"
                                              :state="$v.form.title.$dirty ? !$v.form.title.$error : null"></b-form-input>
                            </b-form-group>
                        </b-col>
                        <b-col md="6">
                            <b-form-group label-for="edit_form_artist">
                                <template v-slot:label v-translate>
                                    Song Artist
                                </template>
                                <b-form-input type="text" id="edit_form_artist" v-model="$v.form.artist.$model"
                                              :state="$v.form.artist.$dirty ? !$v.form.artist.$error : null"></b-form-input>
                            </b-form-group>
                        </b-col>
                        <b-col md="6">
                            <b-form-group label-for="edit_form_album">
                                <template v-slot:label v-translate>
                                    Song Album
                                </template>
                                <b-form-input type="text" id="edit_form_album" v-model="$v.form.album.$model"
                                              :state="$v.form.album.$dirty ? !$v.form.album.$error : null"></b-form-input>
                            </b-form-group>
                        </b-col>
                        <b-col md="6">
                            <b-form-group label-for="edit_form_lyrics">
                                <template v-slot:label v-translate>
                                    Song Lyrics
                                </template>
                                <b-form-textarea id="edit_form_lyrics" v-model="$v.form.lyrics.$model"
                                                 :state="$v.form.lyrics.$dirty ? !$v.form.lyrics.$error : null"></b-form-textarea>
                            </b-form-group>
                        </b-col>
                        <b-col md="6">
                            <b-form-group label-for="edit_form_art">
                                <template v-slot:label v-translate>
                                    Replace Album Cover Art
                                </template>
                                <b-form-file id="edit_form_art" v-model="$v.form.art.$model"
                                             :state="$v.form.art.$dirty ? !$v.form.art.$error : null"></b-form-file>
                            </b-form-group>
                        </b-col>
                        <b-col md="6">
                            <b-form-group label-for="edit_form_isrc">
                                <template v-slot:label v-translate>
                                    ISRC
                                </template>
                                <template v-slot:description v-translate>
                                    International Standard Recording Code, used for licensing reports.
                                </template>
                                <b-form-input type="text" id="edit_form_isrc" v-model="$v.form.isrc.$model"
                                              :state="$v.form.isrc.$dirty ? !$v.form.isrc.$error : null"></b-form-input>
                            </b-form-group>
                        </b-col>
                    </b-row>
                </b-tab>
                <b-tab :title="langCustomFieldsTab">
                    <b-row>

                    </b-row>
                </b-tab>
                <b-tab :title="langAdvancedSettingsTab">
                    <b-row>
                        <b-col md="6">
                            <b-form-group label-for="edit_form_fade_overlap">
                                <template v-slot:label v-translate>
                                    Custom Fading: Overlap Time (seconds)
                                </template>
                                <template v-slot:description v-translate>
                                    The time that this song should overlap its surrounding songs when fading. Leave
                                    blank to use the system default.
                                </template>
                                <b-form-input type="text" id="edit_form_fade_overlap"
                                              v-model="$v.form.fade_overlap.$model"
                                              :state="$v.form.fade_overlap.$dirty ? !$v.form.fade_overlap.$error : null"></b-form-input>
                            </b-form-group>
                        </b-col>
                        <b-col md="6">
                            <b-form-group label-for="edit_form_fade_in">
                                <template v-slot:label v-translate>
                                    Custom Fading: Fade-In Time (seconds)
                                </template>
                                <template v-slot:description v-translate>
                                    The time period that the song should fade in. Leave
                                    blank to use the system default.
                                </template>
                                <b-form-input type="text" id="edit_form_fade_in"
                                              v-model="$v.form.fade_in.$model"
                                              :state="$v.form.fade_in.$dirty ? !$v.form.fade_in.$error : null"></b-form-input>
                            </b-form-group>
                        </b-col>
                        <b-col md="6">
                            <b-form-group label-for="edit_form_fade_out">
                                <template v-slot:label v-translate>
                                    Custom Fading: Fade-Out Time (seconds)
                                </template>
                                <template v-slot:description v-translate>
                                    The time period that the song should fade out. Leave
                                    blank to use the system default.
                                </template>
                                <b-form-input type="text" id="edit_form_fade_out"
                                              v-model="$v.form.fade_out.$model"
                                              :state="$v.form.fade_out.$dirty ? !$v.form.fade_out.$error : null"></b-form-input>
                            </b-form-group>
                        </b-col>
                        <b-col md="6">
                            <b-form-group label-for="edit_form_cue_in">
                                <template v-slot:label v-translate>
                                    Custom Cues: Cue-In Point (seconds)
                                </template>
                                <template v-slot:description v-translate>
                                    Seconds from the start of the song that the AutoDJ should start playing.
                                </template>
                                <b-form-input type="text" id="edit_form_cue_in"
                                              v-model="$v.form.cue_in.$model"
                                              :state="$v.form.cue_in.$dirty ? !$v.form.cue_in.$error : null"></b-form-input>
                            </b-form-group>
                        </b-col>
                        <b-col md="6">
                            <b-form-group label-for="edit_form_cue_out">
                                <template v-slot:label v-translate>
                                    Custom Cues: Cue-Out Point (seconds)
                                </template>
                                <template v-slot:description v-translate>
                                    Seconds from the start of the song that the AutoDJ should stop playing.
                                </template>
                                <b-form-input type="text" id="edit_form_cue_out"
                                              v-model="$v.form.cue_out.$model"
                                              :state="$v.form.cue_out.$dirty ? !$v.form.cue_out.$error : null"></b-form-input>
                            </b-form-group>
                        </b-col>
                    </b-row>
                </b-tab>
            </b-tabs>
        </b-form>
        <template v-slot:modal-footer>
            <b-button variant="default" @click="close" v-translate>
                Close
            </b-button>
            <b-button variant="primary" @click="doEdit" :disabled="$v.form.$invalid" v-translate>
                Save Changes
            </b-button>
        </template>
    </b-modal>
</template>
<script>
  import { validationMixin } from 'vuelidate'
  import axios from 'axios'
  import required from 'vuelidate/src/validators/required'

  export default {
    name: 'EditModal',
    mixins: [validationMixin],
    data () {
      return {
        loading: true,
        recordUrl: null,
        form: this.getBlankForm()
      }
    },
    validations: {
      form: {
        path: {
          required
        },
        title: {},
        artist: {},
        album: {},
        lyrics: {},
        isrc: {},
        art: {},
        fade_overlap: {},
        fade_in: {},
        fade_out: {},
        cue_in: {},
        cue_out: {}
      }
    },
    computed: {
      langTitle () {
        return this.$gettext('Edit Media')
      },
      langBasicInfoTab () {
        return this.$gettext('Basic Information')
      },
      langCustomFieldsTab () {
        return this.$gettext('Custom Fields')
      },
      langAdvancedSettingsTab () {
        return this.$gettext('Advanced Playback Settings')
      }
    },
    methods: {
      getBlankForm () {
        return {
          path: null,
          title: null,
          artist: null,
          album: null,
          lyrics: null,
          isrc: null,
          art: null,
          fade_overlap: null,
          fade_in: null,
          fade_out: null,
          cue_in: null,
          cue_out: null
        }
      },
      open (recordUrl) {
        this.loading = true
        this.$refs.modal.show()

        this.recordUrl = recordUrl

        axios.get(recordUrl).then((resp) => {
          let d = resp.data
          this.form = {
            path: d.path,
            title: d.title,
            artist: d.artist,
            album: d.album,
            lyrics: d.lyrics,
            isrc: d.isrc,
            fade_overlap: d.fade_overlap,
            fade_in: d.fade_in,
            fade_out: d.fade_out,
            cue_in: d.cue_in,
            cue_out: d.cue_out
          }
          this.loading = false
        }).catch((err) => {
          console.log(err)
          this.close()
        })
      },
      close () {
        this.loading = false
        this.form = this.getBlankForm()

        this.$v.form.$reset()
        this.$refs.modal.hide()
      },
      doEdit () {
        this.$v.form.$touch()
        if (this.$v.form.$anyError) {
          return
        }

        axios.put(this.recordUrl, this.form).then((resp) => {
          this.$emit('relist')
          this.close()
        }).catch((err) => {
          console.error(err)
          this.$emit('relist')
          this.close()
        })
      }
    }
  }
</script>