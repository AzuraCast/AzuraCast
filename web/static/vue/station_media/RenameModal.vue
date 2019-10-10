<template>
    <b-modal id="rename_file" centered ref="modal" :title="langRenameFile">
        <form @submit.prevent="doRename">
            <div class="form-group">
                <label for="new_path" class="control-label" v-translate>New File Name:</label>
                <input type="text" class="form-control" id="new_path" v-model="form.newPath" required>
            </div>
        </form>
        <template v-slot:modal-footer>
            <b-button variant="default" @click="close" v-translate>
                Close
            </b-button>
            <b-button variant="primary" @click="doRename" v-translate>
                Rename
            </b-button>
        </template>
    </b-modal>
</template>
<script>
  import axios from 'axios'

  export default {
    name: 'RenameModal',
    props: {
      renameUrl: String
    },
    data () {
      return {
        form: {
          file: null,
          newPath: null
        }
      }
    },
    computed: {
      langRenameFile () {
        return this.$gettext('Rename File/Directory')
      }
    },
    methods: {
      open (filePath) {
        this.form.file = filePath
        this.form.newPath = filePath

        this.$refs.modal.show()
      },
      close () {
        this.$refs.modal.hide()
      },
      doRename () {
        axios.put(this.renameUrl, this.form).then((resp) => {
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