<template>
    <modal
        id="reorder_modal"
        ref="$modal"
        size="lg"
        :title="$gettext('Reorder Playlist')"
        :busy="loading"
        hide-footer
    >
        <div
            style="min-height: 40px;"
            class="flex-fill text-start bg-primary rounded mb-2"
        >
            <inline-player ref="player" />
        </div>

        <table class="table table-striped sortable mb-0">
            <thead>
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
            </thead>
            <draggable
                v-model="media"
                tag="tbody"
                item-key="id"
                @change="save"
            >
                <template #item="{element, index}">
                    <tr class="align-middle">
                        <td class="pe-2">
                            <play-button
                                :url="element.media.links.play"
                            />
                        </td>
                        <td class="ps-2">
                            <span class="typography-subheading">{{ element.media.title }}</span>
                        </td>
                        <td>{{ element.media.artist }}</td>
                        <td>{{ element.media.album }}</td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <button
                                    v-if="index+1 < media.length"
                                    type="button"
                                    class="btn btn-primary"
                                    :title="$gettext('Down')"
                                    @click.prevent="moveDown(index)"
                                >
                                    <icon :icon="IconChevronDown" />
                                </button>
                                <button
                                    v-if="index > 0"
                                    type="button"
                                    class="btn btn-primary"
                                    :title="$gettext('Up')"
                                    @click.prevent="moveUp(index)"
                                >
                                    <icon :icon="IconChevronUp" />
                                </button>
                            </div>
                        </td>
                    </tr>
                </template>
            </draggable>
        </table>
    </modal>
</template>

<script setup lang="ts">
import Draggable from 'vuedraggable';
import Icon from '~/components/Common/Icon.vue';
import PlayButton from "~/components/Common/PlayButton.vue";
import InlinePlayer from '~/components/InlinePlayer.vue';
import {ref} from "vue";
import {useAxios} from "~/vendor/axios";
import {useNotify} from "~/functions/useNotify";
import {useTranslate} from "~/vendor/gettext";
import Modal from "~/components/Common/Modal.vue";
import {IconChevronDown, IconChevronUp} from "~/components/Common/icons";
import {ModalTemplateRef, useHasModal} from "~/functions/useHasModal.ts";

const loading = ref(true);
const reorderUrl = ref(null);
const media = ref([]);

const $modal = ref<ModalTemplateRef>(null);
const {show} = useHasModal($modal);

const {axios} = useAxios();

const open = (newReorderUrl) => {
    reorderUrl.value = newReorderUrl;
    loading.value = true;
    show();

    axios.get(newReorderUrl).then((resp) => {
        media.value = resp.data;
        loading.value = false;
    });
};

const {notifySuccess} = useNotify();
const {$gettext} = useTranslate();

const save = () => {
    const newOrder = {};
    let i = 0;

    media.value.forEach((row) => {
        i++;
        newOrder[row.id] = i;
    });

    axios.put(reorderUrl.value, {'order': newOrder}).then(() => {
        notifySuccess($gettext('Playlist order set.'));
    });
};

const moveDown = (index) => {
    media.value.splice(index + 1, 0, media.value.splice(index, 1)[0]);
    save();
};

const moveUp = (index) => {
    media.value.splice(index - 1, 0, media.value.splice(index, 1)[0]);
    save();
};

defineExpose({
    open
});
</script>

<style lang="scss">
table.sortable {
    cursor: pointer;
}
</style>
