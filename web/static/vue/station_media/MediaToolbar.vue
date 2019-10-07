<template>
    <div class="row pt-4" id="app-toolbar">
        <div class="col-md-8">
            <div class="btn-group dropdown allow-focus">
                <b-dropdown size="sm" variant="primary" ref="setPlaylistsDropdown">
                    <template v-slot:button-content>
                        <i class="material-icons" aria-hidden="true">clear_all</i>
                        <translate>Set Playlists</translate>
                        <span class="caret"></span>
                    </template>
                    <b-dropdown-form class="pt-3" @submit.prevent="setPlaylists">
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
                                           :placeholder="langNewPlaylist">
                                </label>
                            </div>
                        </div>

                        <b-button type="submit" size="sm" variant="primary" v-translate>Save</b-button>
                    </b-dropdown-form>
                </b-dropdown>
            </div>
            <b-button size="sm" variant="warning" @click="clearPlaylists">
                <i class="material-icons" aria-hidden="true">clear_all</i>
                <translate>Clear Playlists</translate>
            </b-button>
            <b-button size="sm" variant="primary" v-b-modal.move_file>
                <i class="material-icons" aria-hidden="true">open_with</i>
                <translate>Move</translate>
            </b-button>
            <b-button size="sm" variant="danger" @click="doDelete">
                <i class="material-icons" aria-hidden="true">delete</i>
                <translate>Delete</translate>
            </b-button>
        </div>
        <div class="col-md-4 text-right">
            <b-button size="sm" variant="primary" v-b-modal.create_directory>
                <i class="material-icons" aria-hidden="true">folder</i>
                <translate>New Folder</translate>
            </b-button>
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
      newPlaylist (text) {
        if (text !== '') {
          if (!this.checkedPlaylists.includes('new')) {
            this.checkedPlaylists.push('new')
          }
        }
      }
    },
    computed: {
      langNewPlaylist () {
        return this.$gettext('New Playlist')
      },
      newPlaylistIsChecked () {
        return this.newPlaylist !== ''
      }
    },
    methods: {
      doDelete (e) {
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
        }).then((resp) => {
          notify('<b>' + notifyMessage + '</b><br>' + this.selectedFiles.join('<br>'), 'success', false)
          this.$emit('relist')
        }).catch((err) => {
          console.error(err)
        })
      },
      clearPlaylists (e) {
        this.checkedPlaylists = []
        this.newPlaylist = ''

        this.setPlaylists(e)
      },
      setPlaylists (e) {
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

          this.$refs.setPlaylistsDropdown.hide()
          this.$emit('relist')
        }).catch((err) => {
          console.error(err)
        })
      }
    }
  }
</script>