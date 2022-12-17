<template>
    <b-tab :title="$gettext('Artwork')">
        <b-form-group>
            <b-row>
                <b-col md="8">
                    <b-form-group label-for="edit_form_art">
                        <template #label>
                            {{ $gettext('Select PNG/JPG artwork file') }}
                        </template>
                        <template #description>
                            {{
                                $gettext('Artwork must be a minimum size of 1400 x 1400 pixels and a maximum size of 3000 x 3000 pixels for Apple Podcasts.')
                            }}
                        </template>
                        <b-form-file id="edit_form_art" accept="image/jpeg, image/png"
                                     @input="uploadNewArt"></b-form-file>
                    </b-form-group>
                </b-col>
                <b-col md="4" v-if="src && src !== ''">
                    <b-img :src="src" :alt="langTitle" rounded fluid></b-img>

                    <div class="buttons pt-3">
                        <b-button block variant="danger" @click="deleteArt">
                            {{ $gettext('Clear Artwork') }}
                        </b-button>
                    </div>
                </b-col>
            </b-row>
        </b-form-group>
    </b-tab>
</template>

<script setup>

import {computed, ref, toRef} from "vue";
import {get, set} from "@vueuse/core";
import {useAxios} from "~/vendor/axios";

const props = defineProps({
    modelValue: Object,
    artworkSrc: String,
    editArtUrl: String,
    newArtUrl: String,
});

const emit = defineEmits(['update:modelValue']);

const artworkSrc = toRef(props, 'artworkSrc');
const localSrc = ref(null);

const src = computed(() => {
    return get(localSrc) ?? get(artworkSrc);
});

const {axios} = useAxios();

const uploadNewArt = (file) => {
    if (!(file instanceof File)) {
        return;
    }

    let fileReader = new FileReader();
    fileReader.addEventListener('load', () => {
        set(localSrc, fileReader.result);
    }, false);
    fileReader.readAsDataURL(file);

    let url = (props.editArtUrl) ? props.editArtUrl : props.newArtUrl;
    let formData = new FormData();
    formData.append('art', file);

    axios.post(url, formData).then((resp) => {
        emit('update:modelValue', resp.data);
    });
};

const deleteArt = () => {
    if (props.editArtUrl) {
        axios.delete(props.editArtUrl).then(() => {
            set(localSrc, null);
        });
    } else {
        set(localSrc, null);
    }
}
</script>

<script>
export default {
    model: {
        prop: 'modelValue',
        event: 'update:modelValue'
    }
};
</script>
