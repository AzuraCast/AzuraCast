<template>
    <modal
        id="apply_playlist_to_modal"
        ref="$modal"
        size="xl"
        centered
        :loading="loading"
        :title="$gettext('Apply Playlist to Folders')"
        @hidden="clearContents"
    >
        <div class="row g-3">
            <div class="col-md-4">
                <form-markup id="apply_to_playlist_name">
                    <template #label>
                        {{ $gettext('Playlist:') }}
                    </template>

                    {{ applyToResults.playlist.name }}
                </form-markup>
            </div>
            <div class="col-md-8">
                <form-group-checkbox
                    id="form_applyto_copy_playlist"
                    :field="v$.copyPlaylist"
                >
                    <template #label>
                        {{ $gettext('Create New Playlist for Each Folder') }}
                    </template>
                </form-group-checkbox>
            </div>
        </div>

        <div style="max-height: 300px; overflow-y: scroll">
            <data-table
                :fields="fields"
                :items="applyToResults.directories"
                :show-toolbar="false"
                selectable
                @row-selected="onRowSelected"
            />
        </div>

        <template #modal-footer>
            <button
                type="button"
                class="btn btn-secondary"
                @click="close"
            >
                {{ $gettext('Close') }}
            </button>
            <button
                type="button"
                class="btn btn-primary"
                @click="save"
            >
                {{ $gettext('Apply to Folders') }}
            </button>
        </template>
    </modal>
</template>

<script setup>
import DataTable from '~/components/Common/DataTable.vue';
import {ref} from "vue";
import {useTranslate} from "~/vendor/gettext";
import {useNotify} from "~/functions/useNotify";
import {useAxios} from "~/vendor/axios";
import FormMarkup from "~/components/Form/FormMarkup.vue";
import {useVuelidateOnForm} from '~/functions/useVuelidateOnForm';
import {map} from "lodash";
import {useResettableRef} from "~/functions/useResettableRef";
import FormGroupCheckbox from "~/components/Form/FormGroupCheckbox.vue";
import Modal from "~/components/Common/Modal.vue";

const emit = defineEmits(['relist']);

const $modal = ref(); // Template Ref

const {$gettext} = useTranslate();

const fields = [
    {
        key: 'name',
        isRowHeader: true,
        label: $gettext('Directory')
    }
];

const loading = ref(true);
const applyToUrl = ref(null);
const {record: applyToResults, reset: resetApplyToResults} = useResettableRef({
    playlist: {
        id: null,
        name: ''
    },
    directories: [],
});

const selectedDirs = ref([]);
const onRowSelected = (items) => {
    selectedDirs.value = map(items, 'path');
};

const {form, v$, resetForm} = useVuelidateOnForm(
    {
        copyPlaylist: {}
    },
    {
        copyPlaylist: false
    }
);

const clearContents = () => {
    applyToUrl.value = null;
    selectedDirs.value = [];

    resetApplyToResults();
    resetForm();
};

const {axios} = useAxios();

const open = (newApplyToUrl) => {
    clearContents();

    applyToUrl.value = newApplyToUrl;
    loading.value = true;
    $modal.value?.show();

    axios.get(newApplyToUrl).then((resp) => {
        applyToResults.value = resp.data;
        loading.value = false;
    });
};

const close = () => {
    $modal.value.hide();
};

const {wrapWithLoading, notifySuccess} = useNotify();

const save = () => {
    v$.value.$touch();
    v$.value.$validate().then((isValid) => {
        if (!isValid) {
            return;
        }

        (selectedDirs.value.length) && wrapWithLoading(
            axios.put(applyToUrl.value, {
                ...form.value,
                directories: selectedDirs.value
            })
        ).then(() => {
            notifySuccess($gettext('Playlist successfully applied to folders.'));
        }).finally(() => {
            close();
            emit('relist');
        });
    });
};

defineExpose({
    open
});
</script>
