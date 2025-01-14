<template>
    <tab :label="$gettext('Artwork')">
        <div class="row g-3">
            <div class="col-md-8">
                <form-group id="edit_form_art">
                    <template #label>
                        {{ $gettext('Select PNG/JPG artwork file') }}
                    </template>
                    <template #description>
                        {{
                            $gettext('Artwork must be a minimum size of 1400 x 1400 pixels and a maximum size of 3000 x 3000 pixels for Apple Podcasts.')
                        }}
                    </template>
                    <template #default="{id}">
                        <form-file
                            :id="id"
                            accept="image/jpeg, image/png"
                            @uploaded="uploaded"
                        />
                    </template>
                </form-group>
            </div>
            <div
                v-if="src && src !== ''"
                class="col-md-4"
            >
                <img
                    :src="src"
                    :alt="$gettext('Artwork')"
                    class="rounded img-fluid"
                >

                <div class="block-buttons pt-3">
                    <button
                        type="button"
                        class="btn btn-block btn-danger"
                        @click="deleteArt"
                    >
                        {{ $gettext('Clear Artwork') }}
                    </button>
                </div>
            </div>
        </div>
    </tab>
</template>

<script setup lang="ts">
import {computed, ref, toRef, watch} from "vue";
import {useAxios} from "~/vendor/axios";
import FormGroup from "~/components/Form/FormGroup.vue";
import FormFile from "~/components/Form/FormFile.vue";
import Tab from "~/components/Common/Tab.vue";

const props = withDefaults(
    defineProps<{
        modelValue?: object,
        artworkSrc?: string,
        newArtUrl: string
    }>(),
    {
        modelValue: null,
        artworkSrc: null,
    }
);

const emit = defineEmits(['update:modelValue']);

const artworkSrc = ref(props.artworkSrc);
const reloadArt = () => {
    artworkSrc.value = props.artworkSrc + '?' + Math.floor(Date.now() / 1000);
}
watch(toRef(props, 'artworkSrc'), reloadArt);

const localSrc = ref(null);

const src = computed(() => {
    return localSrc.value ?? artworkSrc.value;
});

const {axios} = useAxios();

const uploaded = (file) => {
    if (null === file) {
        return;
    }

    const fileReader = new FileReader();
    fileReader.addEventListener('load', () => {
        localSrc.value = fileReader.result;
    }, false);
    fileReader.readAsDataURL(file);

    const url = (props.artworkSrc) ? props.artworkSrc : props.newArtUrl;
    const formData = new FormData();
    formData.append('art', file);

    axios.post(url, formData).then((resp) => {
        emit('update:modelValue', resp.data);
        reloadArt();
    });
};

const deleteArt = () => {
    if (props.artworkSrc) {
        axios.delete(props.artworkSrc).then(() => {
            reloadArt();
            localSrc.value = null;
        });
    } else {
        reloadArt();
        localSrc.value = null;
    }
}
</script>
