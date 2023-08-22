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

<script setup>
import {computed, ref, toRef} from "vue";
import {useAxios} from "~/vendor/axios";
import FormGroup from "~/components/Form/FormGroup.vue";
import FormFile from "~/components/Form/FormFile.vue";
import Tab from "~/components/Common/Tab.vue";

const props = defineProps({
    modelValue: {
        type: Object,
        required: true
    },
    artworkSrc: {
        type: String,
        required: true
    },
    editArtUrl: {
        type: String,
        required: true
    },
    newArtUrl: {
        type: String,
        required: true
    },
});

const emit = defineEmits(['update:modelValue']);

const artworkSrc = toRef(props, 'artworkSrc');
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

    const url = (props.editArtUrl) ? props.editArtUrl : props.newArtUrl;
    const formData = new FormData();
    formData.append('art', file);

    axios.post(url, formData).then((resp) => {
        emit('update:modelValue', resp.data);
    });
};

const deleteArt = () => {
    if (props.editArtUrl) {
        axios.delete(props.editArtUrl).then(() => {
            localSrc.value = null;
        });
    } else {
        localSrc.value = null;
    }
}
</script>
