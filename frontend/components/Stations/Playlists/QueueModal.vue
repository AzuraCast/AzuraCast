<template>
    <modal
        id="queue_modal"
        ref="$modal"
        size="lg"
        :title="$gettext('Playback Queue')"
        :busy="loading"
        @hidden="onHidden"
    >
        <p>
            {{
                $gettext('This queue contains the remaining tracks in the order they will be queued by the AzuraCast AutoDJ (if the tracks are eligible to be played).')
            }}
        </p>

        <table class="table table-striped sortable mb-0">
            <thead>
                <tr>
                    <th style="width: 50%;">
                        {{ $gettext('Title') }}
                    </th>
                    <th style="width: 50%;">
                        {{ $gettext('Artist') }}
                    </th>
                </tr>
            </thead>
            <tbody>
                <tr
                    v-for="row in media"
                    :key="row.spm_id"
                    class="align-middle"
                >
                    <td>
                        <span class="typography-subheading">{{ row.title }}</span>
                    </td>
                    <td>{{ row.artist }}</td>
                </tr>
            </tbody>
        </table>

        <template #modal-footer>
            <button
                class="btn btn-secondary"
                type="button"
                @click="hide"
            >
                {{ $gettext('Close') }}
            </button>
            <button
                class="btn btn-danger"
                type="submit"
                @click="doClear"
            >
                {{ $gettext('Clear Queue') }}
            </button>
        </template>
    </modal>
</template>

<script setup lang="ts">
import {ref, useTemplateRef} from "vue";
import {useAxios} from "~/vendor/axios";
import {useNotify} from "~/components/Common/Toasts/useNotify.ts";
import {useTranslate} from "~/vendor/gettext";
import Modal from "~/components/Common/Modal.vue";
import {useHasModal} from "~/functions/useHasModal.ts";
import {ApiStationPlaylistQueue} from "~/entities/ApiInterfaces.ts";

type MediaRow = Required<ApiStationPlaylistQueue>;

const loading = ref<boolean>(true);
const queueUrl = ref<string | null>(null);
const media = ref<MediaRow[]>([]);

const $modal = useTemplateRef('$modal');
const {show, hide} = useHasModal($modal);

const {axios} = useAxios();

const open = async (newQueueUrl: string) => {
    queueUrl.value = newQueueUrl;
    loading.value = true;

    try {
        const {data} = await axios.get<MediaRow[]>(newQueueUrl);
        media.value = data;
    } finally {
        loading.value = false;
    }

    show();
};

const onHidden = () => {
    loading.value = false;
    queueUrl.value = null;
}

const {notifySuccess} = useNotify();
const {$gettext} = useTranslate();

const doClear = async () => {
    if (queueUrl.value) {
        await axios.delete(queueUrl.value);
    }

    notifySuccess($gettext('Playlist queue cleared.'));
    hide();
};

defineExpose({
    open
});
</script>
