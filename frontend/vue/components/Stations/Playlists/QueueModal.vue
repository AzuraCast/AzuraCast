<template>
    <b-modal
        id="queue_modal"
        ref="$modal"
        size="lg"
        :title="$gettext('Playback Queue')"
        :busy="loading"
    >
        <p>
            {{
                $gettext('This queue contains the remaining tracks in the order they will be queued by the AzuraCast AutoDJ (if the tracks are eligible to be played).')
            }}
        </p>
        <b-overlay
            variant="card"
            :show="loading"
        >
            <b-table-simple
                striped
                class="sortable mb-0"
            >
                <b-thead>
                    <tr>
                        <th style="width: 50%;">
                            {{ $gettext('Title') }}
                        </th>
                        <th style="width: 50%;">
                            {{ $gettext('Artist') }}
                        </th>
                    </tr>
                </b-thead>
                <b-tbody>
                    <tr
                        v-for="row in media"
                        :key="row.id"
                        class="align-middle"
                    >
                        <td>
                            <span class="typography-subheading">{{ row.title }}</span>
                        </td>
                        <td>{{ row.artist }}</td>
                    </tr>
                </b-tbody>
            </b-table-simple>
        </b-overlay>
        <template #modal-footer>
            <b-button
                variant="default"
                type="button"
                @click="close"
            >
                {{ $gettext('Close') }}
            </b-button>
            <b-button
                variant="danger"
                type="submit"
                @click="doClear"
            >
                {{ $gettext('Clear Queue') }}
            </b-button>
        </template>
    </b-modal>
</template>

<script setup>
import {ref} from "vue";
import {useAxios} from "~/vendor/axios";
import {useNotify} from "~/vendor/bootstrapVue";
import {useTranslate} from "~/vendor/gettext";

const loading = ref(true);
const queueUrl = ref(null);
const media = ref([]);

const $modal = ref(); // Template Ref

const {axios} = useAxios();

const open = (newQueueUrl) => {
    queueUrl.value = newQueueUrl;
    loading.value = true;

    axios.get(newQueueUrl).then((resp) => {
        media.value = resp.data;
        loading.value = false;
    });

    $modal.value.show();
};

const close = () => {
    loading.value = false;
    queueUrl.value = null;

    $modal.value.hide();
}

const {wrapWithLoading, notifySuccess} = useNotify();
const {$gettext} = useTranslate();

const doClear = () => {
    wrapWithLoading(
        axios.delete(queueUrl.value)
    ).then(() => {
        notifySuccess($gettext('Playlist queue cleared.'));
        close();
    });
};

defineExpose({
    open
});
</script>
