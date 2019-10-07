<template>
    <b-modal id="create_directory" centered ref="modal" :title="langNewDirectory">
        <form @submit.prevent="doMkdir">
            <div class="form-group">
                <label for="new_directory_name" class="control-label" v-translate>Directory Name:</label>
                <input type="text" class="form-control" id="new_directory_name" v-model="newDirectory">
            </div>
        </form>
        <template v-slot:modal-footer>
            <b-button variant="default" @click="close" v-translate>
                Close
            </b-button>
            <b-button variant="primary" @click="doMkdir" v-translate>
                Create Directory
            </b-button>
        </template>
    </b-modal>
</template>
<script>
  import axios from 'axios'

  export default {
    name: 'NewDirectoryModal',
    props: {
      currentDirectory: String,
      mkdirUrl: String,
      csrf: String
    },
    data () {
      return {
        newDirectory: null
      }
    },
    computed: {
      langNewDirectory () {
        return this.$gettext('New Directory')
      }
    },
    methods: {
      close () {
        this.$refs.modal.hide()
      },
      doMkdir () {
        this.newDirectory.length && axios.post(this.mkdirUrl, {
          name: this.newDirectory,
          csrf: this.csrf,
          file: this.currentDirectory
        }).then((resp) => {
          this.$refs.modal.hide()
          this.$emit('relist')
        }).catch((err) => {
          console.error(err)

          this.$refs.modal.hide()
          this.$emit('relist')
        })
      }
    }
  }
</script>