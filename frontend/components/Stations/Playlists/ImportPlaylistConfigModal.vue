<template>
    <modal
        id="import_playlist_config_modal"
        ref="$modal"
        :title="$gettext('Import Playlist Configuration')"
        size="lg"
        @hidden="onHidden"
    >
        <div v-if="results">
            <p class="card-text">
                {{ results.message }}
            </p>

            <ul class="list-unstyled">
                <li>{{ $gettext('Playlists created:') }} {{ results.playlists_created }}</li>
                <li>{{ $gettext('Media re-linked:') }} {{ results.media_relinked }}</li>
                <li>{{ $gettext('Media generated:') }} {{ results.media_generated }}</li>
                <li>{{ $gettext('Group members created:') }} {{ results.members_created }}</li>
            </ul>

            <div
                v-if="results.warnings.length > 0"
                class="alert alert-warning"
            >
                <ul class="mb-0">
                    <li
                        v-for="(warning, index) in results.warnings"
                        :key="index"
                    >
                        {{ warning }}
                    </li>
                </ul>
            </div>
        </div>
        <form
            v-else
            class="form"
            @submit.prevent="doSubmit"
        >
            <form-group id="import_playlist_config_modal_file">
                <template #label>
                    {{ $gettext('Select JSON Configuration File to Import') }}
                </template>
                <template #description>
                    {{
                        $gettext('New playlists are created from the dump. Any referenced media not present on this station is generated as a silent placeholder file so the playlists are immediately playable.')
                    }}
                </template>

                <template #default="{id}">
                    <form-file
                        :id="id"
                        @uploaded="uploaded"
                    />
                </template>
            </form-group>

            <form-group id="import_playlist_config_modal_prefix">
                <template #label>
                    {{ $gettext('Name Prefix (optional)') }}
                </template>
                <template #description>
                    {{ $gettext('Optionally prepend this text to every imported playlist name.') }}
                </template>

                <template #default="{id}">
                    <input
                        :id="id"
                        v-model="namePrefix"
                        type="text"
                        class="form-control"
                    >
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
                :disabled="playlistsConfigFile === null"
                class="btn btn-primary"
                type="submit"
                @click="doSubmit"
            >
                {{ $gettext('Import') }}
            </button>
        </template>
    </modal>
</template>

<script setup lang="ts">
import { ref, useTemplateRef } from "vue";
import InvisibleSubmitButton from "~/components/Common/InvisibleSubmitButton.vue";
import Modal from "~/components/Common/Modal.vue";
import { useNotify } from "~/components/Common/Toasts/useNotify.ts";
import FormFile from "~/components/Form/FormFile.vue";
import FormGroup from "~/components/Form/FormGroup.vue";
import { ApiStatus } from "~/entities/ApiInterfaces.ts";
import { HasRelistEmit } from "~/functions/useBaseEditModal.ts";
import { useHasModal } from "~/functions/useHasModal.ts";
import { useAxios } from "~/vendor/axios";

type ConfigImportResult = Required<ApiStatus> & {
    playlists_created: number;
    media_relinked: number;
    media_generated: number;
    members_created: number;
    warnings: string[];
};

const props = defineProps<{
    importUrl: string;
}>();

const emit = defineEmits<HasRelistEmit>();

const playlistsConfigFile = ref<File | null>(null);
const namePrefix = ref<string>("");

const results = ref<ConfigImportResult | null>(null);

const uploaded = (file: File) => {
    playlistsConfigFile.value = file;
};

const $modal = useTemplateRef("$modal");
const { show, hide } = useHasModal($modal);

const open = () => {
    playlistsConfigFile.value = null;
    namePrefix.value = "";
    results.value = null;

    show();
};

const { notifySuccess, notifyError } = useNotify();
const { axios } = useAxios();

const doSubmit = async () => {
    if (playlistsConfigFile.value === null) {
        return;
    }

    const formData = new FormData();
    formData.append("config_file", playlistsConfigFile.value);
    if (namePrefix.value !== "") {
        formData.append("name_prefix", namePrefix.value);
    }

    const { data } = await axios.post<ConfigImportResult>(
        props.importUrl,
        formData,
    );

    if (data.success) {
        results.value = data;

        notifySuccess(data.message);
    } else {
        notifyError(data.message);
        hide();
    }
};

const onHidden = () => {
    emit("relist");
    results.value = null;
};

defineExpose({
    open,
});
</script>
