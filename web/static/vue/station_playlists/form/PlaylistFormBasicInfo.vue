<template>
    <b-tab :title="langTabTitle" active>
        <b-form-group>
            <b-row>
                <b-col md="12">
                    <b-form-group label-for="form_edit_is_enabled">
                        <template v-slot:label v-translate>
                            Is Enabled
                        </template>
                        <template v-slot:description v-translate>
                            If set to "No", the playlist will not be included in radio playback, but can still be
                            managed.
                        </template>
                        <b-form-checkbox id="form_edit_is_enabled" v-model="form.is_enabled.$model">
                            <translate>Is Enabled</translate>
                        </b-form-checkbox>
                    </b-form-group>
                </b-col>
                <b-col md="6">
                    <b-form-group label-for="form_edit_name">
                        <template v-slot:label v-translate>
                            Playlist Name
                        </template>
                        <b-form-input type="text" v-model="form.name.$model"
                                      :state="form.name.$dirty ? !form.name.$error : null"></b-form-input>
                        <b-form-invalid-feedback v-translate>
                            This field is required.
                        </b-form-invalid-feedback>
                    </b-form-group>
                </b-col>
                <b-col md="6">
                    <b-form-group label-for="form_edit_weight">
                        <template v-slot:label v-translate>
                            Playlist Weight
                        </template>
                        <template v-slot:description v-translate>
                            Higher weight playlists are played more frequently compared to other lower-weight
                            playlists.
                        </template>
                        <b-form-select id="form_edit_weight" v-model="form.weight.$model" :options="weightOptions"
                                       :state="form.weight.$dirty ? !form.weight.$error : null"></b-form-select>
                        <b-form-invalid-feedback v-translate>
                            This field is required.
                        </b-form-invalid-feedback>
                    </b-form-group>
                </b-col>
            </b-row>
        </b-form-group>
    </b-tab>
</template>

<script>
  export default {
    name: 'PlaylistEditBasicInfo',
    props: {
      form: Object
    },
    data () {
      let weightOptions = [
        { value: 1, text: '1 - ' + this.$gettext('Low') },
        { value: 2, text: '2' },
        { value: 3, text: '3 - ' + this.$gettext('Default') },
        { value: 4, text: '4' },
        { value: 5, text: '5 - ' + this.$gettext('High') }
      ]
      for (var i = 6; i <= 25; i++) {
        weightOptions.push({ value: i, text: i })
      }

      return {
        weightOptions: weightOptions
      }
    },
    computed: {
      langTabTitle () {
        return this.$gettext('Basic Info')
      }
    }
  }
</script>