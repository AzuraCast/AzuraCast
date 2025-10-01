<template>
    <modal
        id="reorder_modal"
        ref="$modal"
        size="lg"
        :title="$gettext('Reorder Playlist')"
        :busy="loading"
        hide-footer
        @shown="onShown"
        @hidden="onHidden"
    >
        <inline-player
            class="text-start bg-primary rounded mb-2 p-1"
            :channel="StreamChannel.Modal"
        />

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
            <tbody ref="$tbody">
                <tr
                    v-for="(element, index) in media"
                    :key="element.media.id"
                    class="align-middle"
                >
                    <td class="pe-2">
                        <play-button
                            :stream="{
                                channel: StreamChannel.Modal,
                                url: element.media.links.play,
                                title: element.media.title
                            }"
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
                                class="btn btn-secondary"
                                :title="$gettext('Move to Bottom')"
                                @click.prevent="moveToBottom(index)"
                            >
                                <icon-bi-chevron-bar-down/>
                            </button>
                            <button
                                v-if="index+1 < media.length"
                                type="button"
                                class="btn btn-primary"
                                :title="$gettext('Move Down')"
                                @click.prevent="moveDown(index)"
                            >
                                <icon-bi-chevron-down/>
                            </button>
                            <button
                                v-if="index > 0"
                                type="button"
                                class="btn btn-primary"
                                :title="$gettext('Move Up')"
                                @click.prevent="moveUp(index)"
                            >
                                <icon-bi-chevron-up/>
                            </button>
                            <button
                                v-if="index > 0"
                                type="button"
                                class="btn btn-secondary"
                                :title="$gettext('Move to Top')"
                                @click.prevent="moveToTop(index)"
                            >
                                <icon-bi-chevron-bar-up/>
                            </button>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </modal>
</template>

<script setup lang="ts">
import PlayButton from "~/components/Common/Audio/PlayButton.vue";
import InlinePlayer from "~/components/InlinePlayer.vue";
import {ref, useTemplateRef} from "vue";
import {useAxios} from "~/vendor/axios";
import {useNotify} from "~/components/Common/Toasts/useNotify.ts";
import {useTranslate} from "~/vendor/gettext";
import Modal from "~/components/Common/Modal.vue";
import {useHasModal} from "~/functions/useHasModal.ts";
import {StreamChannel, usePlayerStore} from "~/functions/usePlayerStore.ts";
import {useDraggable} from "vue-draggable-plus";
import {ApiStationMedia} from "~/entities/ApiInterfaces.ts";
import IconBiChevronBarDown from "~icons/bi/chevron-bar-down";
import IconBiChevronBarUp from "~icons/bi/chevron-bar-up";
import IconBiChevronDown from "~icons/bi/chevron-down";
import IconBiChevronUp from "~icons/bi/chevron-up";

type StationPlaylistMedia = {
    id: number,
    media: Required<ApiStationMedia>
}

const loading = ref(true);
const reorderUrl = ref<string | null>(null);

const $tbody = useTemplateRef('$tbody');
const media = ref<StationPlaylistMedia[]>([]);

const $modal = useTemplateRef('$modal');
const {show} = useHasModal($modal);

const {axios} = useAxios();

const open = (newReorderUrl: string) => {
    reorderUrl.value = newReorderUrl;
    loading.value = true;
    show();

    void (async () => {
        const {data} = await axios.get(newReorderUrl);
        media.value = data;
        loading.value = false;
    })();
};

const {notifySuccess} = useNotify();
const {$gettext} = useTranslate();

const save = async () => {
    if (!reorderUrl.value) {
        return;
    }

    const newOrder: Record<number, number> = {};
    let i = 0;

    media.value.forEach((row) => {
        i++;
        newOrder[row.id] = i;
    });

    await axios.put(reorderUrl.value, {'order': newOrder});
    notifySuccess($gettext('Playlist order set.'));
};

const moveDown = (index: number) => {
    const currentItem = media.value.splice(index, 1)[0];
    media.value.splice(index + 1, 0, currentItem);
    void save();
};

const moveToBottom = (index: number) => {
    const currentItem = media.value.splice(index, 1)[0];
    media.value.splice(media.value.length, 0, currentItem);
    void save();
};

const moveUp = (index: number) => {
    const currentItem = media.value.splice(index, 1)[0];
    media.value.splice(index - 1, 0, currentItem);
    void save();
};

const moveToTop = (index: number) => {
    const currentItem = media.value.splice(index, 1)[0];
    media.value.splice(0, 0, currentItem);
    void save();
};

const {stop} = usePlayerStore();

const onShown = () => {
    useDraggable($tbody, media, {
        onEnd() {
            void save();
        }
    });
};

const onHidden = () => {
    stop();
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
