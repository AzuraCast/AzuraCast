<template>
    <b-tab :title="langTitle" active>
        <b-form-group>
            <b-row>
                <b-form-group class="col-md-6" label-for="edit_form_path">
                    <template v-slot:label v-translate>
                        File Name
                    </template>
                    <template v-slot:description v-translate>
                        The relative path of the file in the station's media directory.
                    </template>
                    <b-form-input type="text" id="edit_form_path" v-model="form.path.$model"
                                  :state="form.path.$dirty ? !form.path.$error : null"></b-form-input>
                    <b-form-invalid-feedback v-translate>
                        This field is required.
                    </b-form-invalid-feedback>
                </b-form-group>
                <b-form-group class="col-md-6" label-for="edit_form_title">
                    <template v-slot:label v-translate>
                        Song Title
                    </template>
                    <b-form-input type="text" id="edit_form_title" v-model="form.title.$model"
                                  :state="form.title.$dirty ? !form.title.$error : null"></b-form-input>
                </b-form-group>
                <b-form-group class="col-md-6" label-for="edit_form_artist">
                    <template v-slot:label v-translate>
                        Song Artist
                    </template>
                    <b-form-input type="text" id="edit_form_artist" v-model="form.artist.$model"
                                  :state="form.artist.$dirty ? !form.artist.$error : null"></b-form-input>
                </b-form-group>
                <b-form-group class="col-md-6" label-for="edit_form_album">
                    <template v-slot:label v-translate>
                        Song Album
                    </template>
                    <b-form-input type="text" id="edit_form_album" v-model="form.album.$model"
                                  :state="form.album.$dirty ? !form.album.$error : null"></b-form-input>
                </b-form-group>
                <b-form-group class="col-md-6" label-for="edit_form_lyrics">
                    <template v-slot:label v-translate>
                        Song Lyrics
                    </template>
                    <b-form-textarea id="edit_form_lyrics" v-model="form.lyrics.$model"
                                     :state="form.lyrics.$dirty ? !form.lyrics.$error : null"></b-form-textarea>
                </b-form-group>
                <b-form-group class="col-md-6" label-for="edit_form_isrc">
                    <template v-slot:label v-translate>
                        ISRC
                    </template>
                    <template v-slot:description v-translate>
                        International Standard Recording Code, used for licensing reports.
                    </template>
                    <b-form-input type="text" id="edit_form_isrc" v-model="form.isrc.$model"
                                  :state="form.isrc.$dirty ? !form.isrc.$error : null"></b-form-input>
                </b-form-group>
            </b-row>
        </b-form-group>
    </b-tab>
</template>

<script>
  export default {
    name: 'MediaFormBasicInfo',
    props: {
      form: Object
    },
    computed: {
      langTitle () {
        return this.$gettext('Basic Information')
      }
    }
  }
</script>