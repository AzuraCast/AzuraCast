<template>
    <modal
        id="import_modal"
        ref="$modal"
        :title="$gettext('Import from PLS/M3U')"
        size="lg"
        @hidden="onHidden"
    >
        <div v-if="results">
            <p class="card-text">
                {{ results.message }}
            </p>

            <div
                class="table-responsive"
                style="max-height: 350px; overflow-y: scroll;"
            >
                <table
                    class="table table-striped"
                    style="max-height: 300px; overflow-y: scroll;"
                >
                    <thead>
                        <tr>
                            <th class="p-2">
                                {{ $gettext('Original Path') }}<br>
                                {{ $gettext('Matched') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="row in results.import_results"
                            :key="row.path"
                        >
                            <td
                                class="p-2 text-monospace"
                                style="overflow-x: auto;"
                            >
                                <pre class="mb-0">{{ row.path }}</pre>
                                <pre
                                    v-if="row.match"
                                    class="mb-0 text-success"
                                >{{ row.match }}</pre>
                                <pre
                                    v-else
                                    class="mb-0 text-danger"
                                >
                                {{ $gettext('No Match') }}
                            </pre>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <form
            v-else
            class="form"
            @submit.prevent="doSubmit"
        >
            <form-group id="import_modal_playlist_file">
                <template #label>
                    {{ $gettext('Select PLS/M3U File to Import') }}
                </template>
                <template #description>
                    {{
                        $gettext('AzuraCast will scan the uploaded file for matches in this station\'s music library. Media should already be uploaded before running this step. You can re-run this tool as many times as needed.')
                    }}
                </template>

                <template #default="{id}">
                    <form-file
                        :id="id"
                        @uploaded="uploaded"
                    />
                </template>
            </form-group>

            <invisible-submit-button />
        </form>
        <template #modal-footer>
            <button
                type="button"
                class="btn btn-secondary"
                @click="close"
            >
                {{ $gettext('Close') }}
            </button>
            <button
                v-if="!results"
                class="btn btn-primary"
                type="submit"
                @click="doSubmit"
            >
                {{ $gettext('Import from PLS/M3U') }}
            </button>
        </template>
    </modal>
</template>

<script setup>
import InvisibleSubmitButton from '~/components/Common/InvisibleSubmitButton';
import {ref} from "vue";
import {useNotify} from "~/functions/useNotify";
import {useAxios} from "~/vendor/axios";
import FormGroup from "~/components/Form/FormGroup.vue";
import Modal from "~/components/Common/Modal.vue";
import FormFile from "~/components/Form/FormFile.vue";

const emit = defineEmits(['relist']);

const importPlaylistUrl = ref(null);
const playlistFile = ref(null);
const overwritePlaylist = ref(false);

const results = ref(null);

const uploaded = (file) => {
    playlistFile.value = file;
}

const $modal = ref(); // Template Ref

const open = (newImportPlaylistUrl) => {
    playlistFile.value = null;
    overwritePlaylist.value = false;

    importPlaylistUrl.value = newImportPlaylistUrl;

    $modal.value.show();
};

const {wrapWithLoading, notifySuccess, notifyError} = useNotify();
const {axios} = useAxios();

const doSubmit = () => {
    const formData = new FormData();
    formData.append('playlist_file', playlistFile.value);


    wrapWithLoading(
        axios.post(importPlaylistUrl.value, formData)
    ).then((resp) => {
        if (resp.data.success) {
            results.value = resp.data;

            notifySuccess(resp.data.message);
        } else {
            notifyError(resp.data.message);
            close();
        }
    });
};

const close = () => {
    $modal.value.hide();
};

const onHidden = () => {
    emit('relist');
    results.value = null;
};

defineExpose({
    open
});
</script>
