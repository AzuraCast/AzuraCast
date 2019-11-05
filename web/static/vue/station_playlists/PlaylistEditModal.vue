<template>
    <b-modal size="lg" id="edit_modal" ref="modal" :title="langTitle" :busy="loading">
        <b-spinner v-if="loading">
        </b-spinner>

        <b-form class="form" v-else @submit.prevent="doSubmit">
            <b-tabs content-class="mt-3">
                <form-basic-info :form="$v.form"></form-basic-info>
                <form-source :form="$v.form"></form-source>
                <form-order :form="$v.form"></form-order>
                <form-schedule :form="$v.form"></form-schedule>
            </b-tabs>
        </b-form>
    </b-modal>
</template>

<script>
  import axios from 'axios'
  import { validationMixin } from 'vuelidate'
  import required from 'vuelidate/src/validators/required'
  import FormBasicInfo from './form/PlaylistFormBasicInfo'
  import FormOrder from './form/PlaylistFormOrder'
  import FormSource from './form/PlaylistFormSource'
  import FormSchedule from './form/PlaylistFormSchedule'

  export default {
    name: 'EditModal',
    components: { FormSchedule, FormSource, FormBasicInfo, FormOrder },
    mixins: [validationMixin],
    props: {
      createUrl: String
    },
    data () {
      return {
        loading: true,
        editUrl: null,
        form: {}
      }
    },
    computed: {
      langTitle () {
        return this.$gettext('Edit Playlist')
      }
    },
    validations: {
      form: {
        'name': { required },
        'is_enabled': { required },
        'weight': { required },
        'type': { required },
        'source': { required },
        'order': { required },
        'remote_url': {},
        'remote_type': {},
        'remote_buffer': {},
        'is_jingle': {},
        'play_per_songs': {},
        'play_per_minutes': {},
        'play_per_hour_minute': {},
        'include_in_requests': {},
        'include_in_automation': {},
        'backend_options': {},
        'schedule_items': {
          $each: {}
        }
      }
    },
    methods: {
      resetForm () {
        this.form = {
          'name': '',
          'is_enabled': true,
          'weight': 3,
          'type': 'default',
          'source': 'songs',
          'order': 'shuffle',
          'remote_url': null,
          'remote_type': 'stream',
          'remote_buffer': 0,
          'is_jingle': false,
          'play_per_songs': 0,
          'play_per_minutes': 0,
          'play_per_hour_minute': 0,
          'include_in_requests': true,
          'include_in_automation': false,
          'backend_options': [],
          'schedule_items': []
        }
      },
      create () {
        this.resetForm()
        this.loading = false
        this.editUrl = null

        this.$refs.modal.show()
      },
      edit (recordUrl) {
        this.resetForm()
        this.loading = true
        this.$refs.modal.show()

        axios.get(recordUrl).then((resp) => {
          let d = resp.data

          this.form = {
            'name': d.name,
            'is_enabled': d.is_enabled,
            'weight': d.weight,
            'type': d.type,
            'source': d.source,
            'order': d.order,
            'remote_url': d.remote_url,
            'remote_type': d.remote_type,
            'remote_buffer': d.remote_buffer,
            'is_jingle': d.is_jingle,
            'play_per_songs': d.play_per_songs,
            'play_per_minutes': d.play_per_minutes,
            'play_per_hour_minute': d.play_per_hour_minute,
            'include_in_requests': d.include_in_requests,
            'include_in_automation': d.include_in_automation,
            'backend_options': d.backend_options,
            'schedule_items': d.schedule_items
          }

          this.loading = false
        }).catch((err) => {
          console.log(err)
          this.close()
        })
      },
      doSubmit () {
        this.$v.form.$touch()
        if (this.$v.form.$anyError) {
          return
        }

        axios({
          method: (this.editUrl === null) ? 'POST' : 'PUT',
          url: (this.editUrl === null) ? this.createUrl : this.editUrl,
          data: this.form
        }).then((resp) => {
          let notifyMessage = this.$gettext('Changes saved.')
          notify('<b>' + notifyMessage + '</b>', 'success', false)

          this.$emit('relist')
          this.close()
        }).catch((err) => {
          console.error(err)

          let notifyMessage = this.$gettext('An error occurred and your request could not be completed.')
          notify('<b>' + notifyMessage + '</b>', 'danger', false)

          this.$emit('relist')
          this.close()
        })
      }
    },
    close () {
      this.loading = false
      this.editUrl = null
      this.resetForm()

      this.$v.form.$reset()
      this.$refs.modal.hide()
    }
  }
</script>