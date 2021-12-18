<template>
    <b-modal size="lg" id="reorder_modal" ref="modal" :title="langTitle" :busy="loading" hide-footer>
        <b-overlay variant="card" :show="loading">
            <div style="min-height: 40px;" class="flex-fill text-left bg-primary rounded">
              <inline-player ref="player"></inline-player>
            </div>
            <b-table-simple striped class="sortable mb-0">
                <b-thead>
                    <tr>
                        <th style="width: 34%;" key="lang_col_title" v-translate>Title</th>
                        <th style="width: 23%;" key="lang_col_artist" v-translate>Artist</th>
                        <th style="width: 23%;" key="lang_col_album" v-translate>Album</th>
                        <th style="width: 20%;" key="lang_col_actions" v-translate>Actions</th>
                    </tr>
                </b-thead>
                <draggable v-model="media" tag="tbody" @change="save">
                    <tr class="align-middle" v-for="(row,index) in media" :key="media.id">
                        <td>
                            <div style="display: flex; align-items: center; justify-content: flex-start;">
                                <div style="font-size: 1.1rem; margin-right: 1rem;">
                                    <play-button :url="row.playback_url" icon-class="outlined"></play-button>
                                </div>
                                <div class="flex-fill">
                                    <big>{{ row.media.title }}</big>
                                </div>
                            </div>
                        </td>
                        <td>{{ row.media.artist }}</td>
                        <td>{{ row.media.album }}</td>
                        <td>
                            <b-button-group size="sm">
                                <b-button size="sm" variant="primary" @click.prevent="moveDown(index)" :title="langDownBtn"
                                          v-if="index+1 < media.length">
                                    <icon icon="arrow_downward"></icon>
                                </b-button>
                                <b-button size="sm" variant="primary" @click.prevent="moveUp(index)" :title="langUpBtn"
                                          v-if="index > 0">
                                    <icon icon="arrow_upward"></icon>
                                </b-button>
                            </b-button-group>
                        </td>
                    </tr>
                </draggable>
            </b-table-simple>
        </b-overlay>
    </b-modal>
</template>

<style lang="scss">
table.sortable {
    cursor: pointer;
}
</style>

<script>
import Draggable from 'vuedraggable';
import Icon from '~/components/Common/Icon';
import PlayButton from "~/components/Common/PlayButton";
import InlinePlayer from '../../InlinePlayer';

export default {
    name: 'ReorderModal',
    components: {
        Icon,
        Draggable,
        PlayButton,
        InlinePlayer
    },
    data() {
        return {
            loading: true,
            reorderUrl: null,
            media: []
        };
    },
    computed: {
        langTitle () {
            return this.$gettext('Reorder Playlist');
        },
        langDownBtn () {
            return this.$gettext('Down');
        },
        langUpBtn () {
            return this.$gettext('Up');
        }
    },
    methods: {
        open (reorderUrl) {
            this.$refs.modal.show();
            this.reorderUrl = reorderUrl;
            this.loading = true;

            this.axios.get(this.reorderUrl).then((resp) => {
                // this is a bit of a hack to get the api_url & station_id; suggetions welcome.
                let playbackBaseUrl = "";
                if (this.reorderUrl.includes("://")) {
                    playbackBaseUrl = this.reorderUrl.match(/.*\/station\/(\d+)\//i)?.[0] || "";
                } else {
                    playbackBaseUrl = 
                      window.location.protocol 
                      + "//" 
                      + window.location.host 
                      + this.reorderUrl.match(/.*\/station\/(\d+)\//i)?.[0] || ""; // selects all after domain return "/api/station/{id}/" or ""
                }
                playbackBaseUrl += "files/play/";

                // add playback url to each media object
                this.media = resp.data.map( obj => {
                  const playbackUrl = playbackBaseUrl + obj.media_id;
                  return {
                    ...obj, 
                    playback_url: playbackUrl
                  }
                });
                this.loading = false;
            });
        },
        moveDown (index) {
            this.media.splice(index + 1, 0, this.media.splice(index, 1)[0]);
            this.save();
        },
        moveUp (index) {
            this.media.splice(index - 1, 0, this.media.splice(index, 1)[0]);
            this.save();
        },
        save () {
            let newOrder = {};
            let i = 0;

            this.media.forEach((row) => {
                i++;
                newOrder[row.id] = i;
            });

            this.axios.put(this.reorderUrl, {'order': newOrder}).then((resp) => {
                this.$notifySuccess(this.$gettext('Playlist order set.'));
            });
        },
    }
};
</script>
