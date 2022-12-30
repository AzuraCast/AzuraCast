<template>
    <b-modal
        id="reorder_modal"
        ref="modal"
        size="lg"
        :title="$gettext('Reorder Playlist')"
        :busy="loading"
        hide-footer
    >
        <b-overlay
            variant="card"
            :show="loading"
        >
            <div
                style="min-height: 40px;"
                class="flex-fill text-left bg-primary rounded mb-2"
            >
                <inline-player ref="player" />
            </div>
            <b-table-simple
                striped
                class="sortable mb-0"
            >
                <b-thead>
                    <tr>
                        <th style="width: 5%">
&nbsp;
                        </th>
                        <th style="width: 25%;">
                            {{ $gettext('Title') }}
                        </th>
                        <th style="width: 25%;">
                            {{ $gettext('Artist') }}
                        </th>
                        <th style="width: 25%;">
                            {{ $gettext('Album') }}
                        </th>
                        <th style="width: 20%;">
                            {{ $gettext('Actions') }}
                        </th>
                    </tr>
                </b-thead>
                <draggable
                    v-model="media"
                    tag="tbody"
                    @change="save"
                >
                    <tr
                        v-for="(row,index) in media"
                        :key="row.media.id"
                        class="align-middle"
                    >
                        <td class="pr-2">
                            <play-button
                                :url="row.media.links.play"
                                icon-class="lg outlined"
                            />
                        </td>
                        <td class="pl-2">
                            <span class="typography-subheading">{{ row.media.title }}</span>
                        </td>
                        <td>{{ row.media.artist }}</td>
                        <td>{{ row.media.album }}</td>
                        <td>
                            <b-button-group size="sm">
                                <b-button
                                    v-if="index+1 < media.length"
                                    size="sm"
                                    variant="primary"
                                    :title="$gettext('Down')"
                                    @click.prevent="moveDown(index)"
                                >
                                    <icon icon="arrow_downward" />
                                </b-button>
                                <b-button
                                    v-if="index > 0"
                                    size="sm"
                                    variant="primary"
                                    :title="$gettext('Up')"
                                    @click.prevent="moveUp(index)"
                                >
                                    <icon icon="arrow_upward" />
                                </b-button>
                            </b-button-group>
                        </td>
                    </tr>
                </draggable>
            </b-table-simple>
        </b-overlay>
    </b-modal>
</template>

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

            this.axios.put(this.reorderUrl, {'order': newOrder}).then(() => {
                this.$notifySuccess(this.$gettext('Playlist order set.'));
            });
        },
    }
};
</script>

<style lang="scss">
table.sortable {
    cursor: pointer;
}
</style>
