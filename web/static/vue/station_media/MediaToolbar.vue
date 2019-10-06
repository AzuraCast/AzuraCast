<template>
    <div class="row pt-4" id="app-toolbar">
        <div class="col-md-8">
            <div class="btn-group dropdown allow-focus">
                <button type="button" class="btn btn-sm btn-primary dropdown-toggle mb-1" data-toggle="dropdown"
                        aria-expanded="false">
                    <i class="material-icons" aria-hidden="true">clear_all</i>
                    <translate>Set Playlists</translate>
                    <span class="caret"></span>
                </button>
                <div class="dropdown-menu" role="menu" @click.stop="" style="line-height: inherit">
                    <form id="frm_set_playlists" @submit.stop.prevent="setPlaylists" class="px-3 py-3">
                        <div v-for="playlist in playlists" class="form-group">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input"
                                       v-bind:id="'chk_playlist_' + playlist.id" name="playlists[]"
                                       v-model="checkedPlaylists" v-bind:value="playlist.id">
                                <label class="custom-control-label" v-bind:for="'chk_playlist_'+playlist.id">
                                    {{ playlist.name }}
                                </label>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="chk_playlist_new"
                                       v-model="checkedPlaylists" value="new">
                                <label class="custom-control-label" for="chk_playlist_new">
                                    <input type="text" class="form-control p-2" id="new_playlist_name"
                                           name="new_playlist_name" v-model="newPlaylist"
                                           placeholder="<?=__('New Playlist') ?>">
                                </label>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-sm btn-primary" v-translate>Save</button>
                    </form>
                </div>
            </div>
            <button type="button" class="btn btn-sm btn-warning mb-1" @click.stop.prevent="clearPlaylists">
                <i class="material-icons" aria-hidden="true">clear_all</i>
                <translate>Clear Playlists</translate>
            </button>
            <a href="#" class="btn btn-sm btn-primary mb-1" data-toggle="modal" data-target="#mdl-move-file">
                <i class="material-icons" aria-hidden="true">open_with</i>
                <translate>Move</translate>
            </a>
            <button type="button" class="btn btn-sm btn-danger mb-1" @click.stop.prevent="doDelete">
                <i class="material-icons" aria-hidden="true">delete</i>
                <translate>Delete</translate>
            </button>
        </div>
        <div class="col-md-4 text-right">
            <a class="btn btn-sm btn-primary" href="#" data-toggle="modal" data-target="#mdl-create-directory">
                <i class="material-icons" aria-hidden="true">folder</i>
                <translate>New Folder</translate>
            </a>
        </div>
    </div>
</template>
<script>
  import axios from 'axios'

  export default {
    name: 'station-media-toolbar',
    props: {
      currentDirectory: String,
      selectedFiles: Array,
      initialPlaylists: Array,
      batchUrl: String,
      csrf: String
    },
    data () {
      return {
        playlists: this.initialPlaylists,
        checkedPlaylists: [],
        newPlaylist: ''
      }
    },
    watch: {
      newPlaylist: function (text) {
        if (text !== '') {
          if (!this.checkedPlaylists.includes('new')) {
            this.checkedPlaylists.push('new')
          }
        }
      }
    },
    computed: {
      newPlaylistIsChecked: function () {
        return this.newPlaylist !== ''
      }
    },
    methods: {
      doDelete: function (e) {
        let buttonText = this.$gettext('Delete')
        let buttonConfirmText = this.$gettext('Delete %{ num } media file(s)?')

        swal({
          title: this.$gettextInterpolate(buttonConfirmText, { num: this.selectedFiles.length }),
          buttons: [true, buttonText],
          dangerMode: true
        }).then((value) => {
          if (value) {
            this.doBatch('delete', this.$gettext('Files removed:'))
          }
        })
      },
      doBatch (action, notifyMessage) {
        this.selectedFiles.length && axios.post(this.batchUrl, {
          'do': action,
          'files': this.selectedFiles,
          'csrf': this.csrf,
          'file': this.currentDirectory
        }, () => {
          notify('<b>' + notifyMessage + '</b><br>' + this.selectedFiles.join('<br>'), 'success', false)
          this.$emit('relist')
        }, 'json')
      },
      clearPlaylists (e) {
        this.checkedPlaylists = []
        this.newPlaylist = ''

        this.setPlaylists(e)
      },
      setPlaylists: function (e) {
        this.selectedFiles.length && axios.post(this.batchUrl, {
          'do': 'playlist',
          'playlists': this.checkedPlaylists,
          'new_playlist_name': this.newPlaylist,
          'files': this.selectedFiles,
          'csrf': this.csrf,
          'file': this.currentDirectory
        }).then((resp) => {
          if (resp.data.success && resp.data.record) {
            this.playlists.push(resp.data.record)
          }

          let notifyMessage = (this.checkedPlaylists.length > 0)
            ? this.$gettext('Playlists updated for selected files:')
            : this.$gettext('Playlists cleared for selected files:')
          notify('<b>' + notifyMessage + '</b><br>' + this.selectedFiles.join('<br>'), 'success', false)

          this.checkedPlaylists = []
          this.newPlaylist = ''

          $(e.target).closest('.dropdown').find('[data-toggle=dropdown]').dropdown('toggle')

          this.$emit('relist')
        }).catch((err) => {
          console.error(err)
        })
      }
    }
  }
</script>