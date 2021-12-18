<template>
    <b-modal size="lg" id="reorder_modal" ref="modal" :title="langTitle" :busy="loading" hide-footer>
        <b-overlay variant="card" :show="loading">
            <div style="min-height: 40px;" class="flex-fill text-left bg-primary rounded mb-2">
                <inline-player ref="player"></inline-player>
            </div>
            <b-table-simple striped class="sortable mb-0">
                <b-thead>
                    <tr>
                        <th style="width: 5%">&nbsp;</th>
                        <th style="width: 25%;" key="lang_col_title" v-translate>Title</th>
                        <th style="width: 25%;" key="lang_col_artist" v-translate>Artist</th>
                        <th style="width: 25%;" key="lang_col_album" v-translate>Album</th>
                        <th style="width: 20%;" key="lang_col_actions" v-translate>Actions</th>
                    </tr>
                </b-thead>
                <draggable v-model="media" tag="tbody" @change="save">
                    <tr class="align-middle" v-for="(row,index) in media" :key="media.id">
                        <td class="pr-2">
                            <play-button :url="row.media.links.play" icon-class="lg outlined"></play-button>
                        </td>
                        <td class="pl-2">
                            <big>{{ row.media.title }}</big>
                        </td>
                        <td>{{ row.media.artist }}</td>
                        <td>{{ row.media.album }}</td>
                        <td>
                            <b-button-group size="sm">
                                <b-button size="sm" variant="primary" @click.prevent="moveDown(index)"
                                          :title="langDownBtn"
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
import InlinePlayer from '~/components/InlinePlayer';

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
                this.media = resp.data;
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
