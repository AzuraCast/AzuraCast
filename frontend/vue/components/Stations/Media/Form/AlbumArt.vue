<template>
    <b-form-group>
        <b-row>
            <b-col md="4">
                <b-img :src="albumArtSrc" rounded fluid></b-img>
                <br>
                <b-button block variant="link" class="text-danger mt-2" @click="deleteArt">
                    {{ $gettext('Delete Album Art') }}
                </b-button>
            </b-col>
            <b-col md="8">
                <b-form-group label-for="edit_form_art">
                    <template #label>
                        {{ $gettext('Replace Album Cover Art') }}
                    </template>
                    <b-form-file id="edit_form_art" v-model="artFile" accept="image/*"></b-form-file>
                </b-form-group>
            </b-col>
        </b-row>
    </b-form-group>
</template>

<script setup>
import {ref, toRef, watch} from "vue";
import {syncRef} from "@vueuse/core";
import {useAxios} from "~/vendor/axios";

const props = defineProps({
    albumArtUrl: String
});

const albumArtSrc = ref(null);
syncRef(toRef(props, 'albumArtUrl'), albumArtSrc, {direction: 'ltr'});

const artFile = ref(null);

const reloadArt = () => {
    artFile.value = null;
    albumArtSrc.value = props.albumArtUrl + '?' + Math.floor(Date.now() / 1000);
}
watch(toRef(props, 'albumArtUrl'), reloadArt);

const {axios} = useAxios();

watch(artFile, (file) => {
    if (null === file) {
        return;
    }

    let formData = new FormData();
    formData.append('art', file);

    axios.post(props.albumArtUrl, formData).finally(() => {
        reloadArt();
    });
});

const deleteArt = () => {
    axios.delete(props.albumArtUrl).finally(() => {
        reloadArt();
    });
};
</script>
