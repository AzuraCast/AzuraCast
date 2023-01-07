<template>
    <b-modal
        id="import_modal"
        ref="$modal"
        :title="$gettext('Import from PLS/M3U')"
        @hidden="onHidden"
    >
        <div v-if="results">
            <p class="card-text">
                {{ results.message }}
            </p>

            <b-table-simple
                striped
                responsive
                style="max-height: 300px; overflow-y: scroll;"
            >
                <b-thead>
                    <b-tr>
                        <b-th class="p-2">
                            {{ $gettext('Original Path') }}
                            <br>
                            {{ $gettext('Matched') }}
                        </b-th>
                    </b-tr>
                </b-thead>
                <b-tbody>
                    <b-tr
                        v-for="row in results.import_results"
                        :key="row.path"
                    >
                        <b-td
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
                        </b-td>
                    </b-tr>
                </b-tbody>
            </b-table-simple>
        </div>
        <b-form
            v-else
            class="form"
            @submit.prevent="doSubmit"
        >
            <b-form-group label-for="import_modal_playlist_file">
                <template #label>
                    {{ $gettext('Select PLS/M3U File to Import') }}
                </template>
                <template #description>
                    {{
                        $gettext('AzuraCast will scan the uploaded file for matches in this station\'s music library. Media should already be uploaded before running this step. You can re-run this tool as many times as needed.')
                    }}
                </template>
                <b-form-file
                    id="import_modal_playlist_file"
                    v-model="playlistFile"
                />
            </b-form-group>

            <invisible-submit-button />
        </b-form>
        <template #modal-footer>
            <b-button
                variant="default"
                type="button"
                @click="close"
            >
                {{ $gettext('Close') }}
            </b-button>
            <b-button
                v-if="!results"
                variant="primary"
                type="submit"
                @click="doSubmit"
            >
                {{ $gettext('Import from PLS/M3U') }}
            </b-button>
        </template>
    </b-modal>
</template>

<script setup>
import InvisibleSubmitButton from '~/components/Common/InvisibleSubmitButton';
import {ref} from "vue";
import {useNotify} from "~/vendor/bootstrapVue";
import {useAxios} from "~/vendor/axios";

const emit = defineEmits(['relist']);

const importPlaylistUrl = ref(null);
const playlistFile = ref(null);
const results = ref(null);

const $modal = ref(); // Template Ref

const open = (newImportPlaylistUrl) => {
    playlistFile.value = null;
    importPlaylistUrl.value = newImportPlaylistUrl;

    $modal.value.show();
};

const {wrapWithLoading, notifySuccess, notifyError} = useNotify();
const {axios} = useAxios();

const doSubmit = () => {
    let formData = new FormData();
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
