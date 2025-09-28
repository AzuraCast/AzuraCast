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
                @click="hide"
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

<script setup lang="ts">
import InvisibleSubmitButton from "~/components/Common/InvisibleSubmitButton.vue";
import {ref, useTemplateRef} from "vue";
import {useNotify} from "~/components/Common/Toasts/useNotify.ts";
import {useAxios} from "~/vendor/axios";
import FormGroup from "~/components/Form/FormGroup.vue";
import Modal from "~/components/Common/Modal.vue";
import FormFile from "~/components/Form/FormFile.vue";
import {useHasModal} from "~/functions/useHasModal.ts";
import {HasRelistEmit} from "~/functions/useBaseEditModal.ts";
import {ApiStatus} from "~/entities/ApiInterfaces.ts";

type BatchImportResult = Required<ApiStatus> & {
    import_results: {
        path: string,
        match: string | null
    }[]
}

const emit = defineEmits<HasRelistEmit>();

const importPlaylistUrl = ref<string | null>(null);
const playlistFile = ref<File | null>(null);
const overwritePlaylist = ref(false);

const results = ref<BatchImportResult | null>(null);

const uploaded = (file: File) => {
    playlistFile.value = file;
}

const $modal = useTemplateRef('$modal');
const {show, hide} = useHasModal($modal);

const open = (newImportPlaylistUrl: string) => {
    playlistFile.value = null;
    overwritePlaylist.value = false;

    importPlaylistUrl.value = newImportPlaylistUrl;
    show();
};

const {notifySuccess, notifyError} = useNotify();
const {axios} = useAxios();

const doSubmit = async () => {
    if (playlistFile.value === null || importPlaylistUrl.value === null) {
        return;
    }

    const formData = new FormData();
    formData.append('playlist_file', playlistFile.value);

    const {data} = await axios.post<BatchImportResult>(importPlaylistUrl.value, formData);

    if (data.success) {
        results.value = data;

        notifySuccess(data.message);
    } else {
        notifyError(data.message);
        hide();
    }
};

const onHidden = () => {
    emit('relist');
    results.value = null;
};

defineExpose({
    open
});
</script>
