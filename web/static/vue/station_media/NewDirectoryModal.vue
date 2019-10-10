<template>
    <b-modal id="create_directory" centered ref="modal" :title="langNewDirectory">
        <b-form @submit.prevent="doMkdir">
            <b-form-group label-for="new_directory_name">
                <template v-slot:label v-translate>
                    Directory Name
                </template>
                <b-input type="text" id="new_directory_name" v-model="$v.newDirectory.$model"
                         :state="$v.newDirectory.$dirty ? !$v.newDirectory.$error : null"></b-input>
                <b-form-invalid-feedback v-translate>
                    This field is required.
                </b-form-invalid-feedback>
            </b-form-group>
        </b-form>
        <template v-slot:modal-footer>
            <b-button variant="default" @click="close" v-translate>
                Close
            </b-button>
            <b-button variant="primary" @click="doMkdir" :disabled="$v.$invalid" v-translate>
                Create Directory
            </b-button>
        </template>
    </b-modal>
</template>
<script>
  import { validationMixin } from 'vuelidate'
  import { required } from 'vuelidate/lib/validators'
  import axios from 'axios'

  export default {
    name: 'NewDirectoryModal',
    mixins: [validationMixin],
    props: {
      currentDirectory: String,
      mkdirUrl: String
    },
    data () {
      return {
        newDirectory: null
      }
    },
    validations: {
      newDirectory: {
        required
      }
    },
    computed: {
      langNewDirectory () {
        return this.$gettext('New Directory')
      }
    },
    methods: {
      close () {
        this.newDirectory = null
        this.$v.$reset()
        this.$refs.modal.hide()
      },
      doMkdir () {
        this.$v.$touch()
        if (this.$v.$anyError) {
          return
        }

        axios.post(this.mkdirUrl, {
          name: this.newDirectory,
          file: this.currentDirectory
        }).then((resp) => {
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