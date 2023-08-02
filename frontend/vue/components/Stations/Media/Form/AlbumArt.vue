<template>
    <div class="row g-3">
        <div class="col-md-4">
            <img
                :src="albumArtSrc"
                class="rounded img-fluid"
                alt="Album Art"
            >

            <div class="block-buttons mt-2">
                <button
                    type="button"
                    class="btn btn-link btn-block btn-danger"
                    @click="deleteArt"
                >
                    {{ $gettext('Delete Album Art') }}
                </button>
            </div>
        </div>
        <div class="col-md-8">
            <form-group id="edit_form_art">
                <template #label>
                    {{ $gettext('Replace Album Cover Art') }}
                </template>

                <template #default="{id}">
                    <form-file
                        :id="id"
                        accept="image/*"
                        @uploaded="uploaded"
                    />
                </template>
            </form-group>
        </div>
    </div>
</template>

<script setup>
import {ref, toRef, watch} from "vue";
import {syncRef} from "@vueuse/core";
import {useAxios} from "~/vendor/axios";
import FormGroup from "~/components/Form/FormGroup.vue";
import FormFile from "~/components/Form/FormFile.vue";

const props = defineProps({
    albumArtUrl: {
        type: String,
        required: true
    }
});

const albumArtSrc = ref(null);
syncRef(toRef(props, 'albumArtUrl'), albumArtSrc, {direction: 'ltr'});

const reloadArt = () => {
    albumArtSrc.value = props.albumArtUrl + '?' + Math.floor(Date.now() / 1000);
}
watch(toRef(props, 'albumArtUrl'), reloadArt);

const {axios} = useAxios();

const uploaded = (file) => {
    if (null === file) {
        return;
    }

    const formData = new FormData();
    formData.append('art', file);

    axios.post(props.albumArtUrl, formData).finally(() => {
        reloadArt();
    });
};

const deleteArt = () => {
    axios.delete(props.albumArtUrl).finally(() => {
        reloadArt();
    });
};
</script>
