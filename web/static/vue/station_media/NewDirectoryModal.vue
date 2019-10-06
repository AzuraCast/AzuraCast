<template>
    <div class="modal fade" id="mdl-create-directory" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <form id="frm-create-directory" @submit.prevent="doMkdir">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title" id="exampleModalLabel" v-translate>New Directory</h4>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="new_directory_name" class="control-label" v-translate>Directory Name:</label>
                            <input type="text" class="form-control" id="new_directory_name" v-model="newDirectory">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal" v-translate>Close</button>
                        <button type="submit" id="btn-create-new-playlist" class="btn btn-primary" v-translate>
                            Create Directory
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
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
    methods: {
      doMkdir () {
        this.newDirectory.length && axios.post(this.mkdirUrl, {
          name: this.newDirectory,
          csrf: this.csrf,
          file: this.currentDirectory
        }).then((resp) => {
          $('#mdl-create-directory').modal('hide')
          this.$emit('relist')
        }).catch((err) => {
          console.error(err)

          $('#mdl-create-directory').modal('hide')
          this.$emit('relist')
        })
      }
    }
  }
</script>