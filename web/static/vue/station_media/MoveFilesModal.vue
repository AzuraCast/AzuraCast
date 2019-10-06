<template>
    <div class="modal fade" id="mdl-move-file" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <form id="frm-move-file">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title" id="exampleModalLabel">{{ lang_header }}</h4>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-sm-12">
                                <button type="button" class="btn btn-primary" v-on:click="pageBack"
                                        :disabled="dirHistory.length === 0">
                                    <i class="material-icons" aria-hidden="true">chevron_left</i>
                                    <translate>Back</translate>
                                </button>
                                <span>&nbsp;{{ destinationDirectory }}</span>
                                <br/><br/>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-sm-12">
                                <data-table ref="datatable" id="station_media" :fields="fields"
                                            :api-url="listDirectoriesUrl" :request-config="requestConfig">
                                    <template v-slot:cell(directory)="row">
                                        <div class="is_dir">
                                            <span class="file-icon">
                                                <i class="material-icons" aria-hidden="true">folder</i>
                                            </span>

                                            <a href="#" @click.prevent="enterDirectory(row.path)">{{ row.name }}</a>
                                        </div>
                                    </template>
                                </data-table>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal" v-translate>
                            Close
                        </button>
                        <button type="button" class="btn btn-primary" @click.prevent="doMove()" v-translate>
                            Move to Directory
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</template>
<script>
  import DataTable from '../components/DataTable.vue'

  export default {
    name: 'MoveFilesModal',
    components: { DataTable },
    props: {
      selectedFiles: Array,
      currentDirectory: File,
      batchUrl: String,
      listDirectoriesUrl: String,
      csrf: String
    },
    data () {
      return {
        destinationDirectory: '',
        dirHistory: [],
        fields: [
          { id: 'directory', label: this.$gettext('Directory'), sortable: false }
        ]
      }
    },
    computed: {
      lang_header () {
        let headerText = this.$gettext('Move %{ num } File(s) to')
        return this.$gettextInterpolate(headerText, { num: this.selectedFiles.length })
      }
    },
    methods: {
      doMove () {
        this.selectedFiles.length && axios.post(this.batchUrl, {
          'do': 'move',
          'files': this.selectedFiles,
          'csrf': this.csrf,
          'directory': this.destinationDirectory
        }).then((resp) => {
          this.$emit('relist')
          $('#mdl-move-file').modal('hide')
        }).catch((err) => {
          this.$emit('relist')
          $('#mdl-move-file').modal('hide')
        })
      },
      enterDirectory (path) {
        this.dirHistory.push(path)
        this.destinationDirectory = path

        this.$refs.datatable.refresh()
      },
      pageBack: function (e) {
        e.preventDefault()

        this.dirHistory.pop()
        this.destinationDirectory = this.dirHistory.slice(-1)[0]

        this.$refs.datatable.refresh()
      },
      requestConfig (config) {
        config.params.file = this.destinationDirectory
        config.params.csrf = this.csrf

        return config
      }
    }
  }
</script>